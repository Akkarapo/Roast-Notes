# Plan: ordering

> อ้างอิง [docs/specs/SPEC-ordering.md](../docs/specs/SPEC-ordering.md) · หน้าตา [DESIGN.md](../docs/specs/DESIGN.md) · สถานะ: **เสร็จแล้ว (2026-10-21)**
> งานโมดูลก่อนหน้าอยู่ที่ `tasks/archive/`

## องค์ประกอบและลำดับ

```
O1 Schema + enums (Grind, OrderStatus) + models + factories
 ├─ O2 Pricing: PriceCalculator, DiscountRule contract, Quote — pure + unit tests
 │   └─ O3 ใส่ตะกร้า: AddToCart + ฟอร์มหน้าสินค้า + badge ใน navbar
 │       └─ O4 หน้าตะกร้า: แก้จำนวน/การบด/ลบ + กล่องสรุป
 │           └─ O5 Checkout: PlaceOrder (transaction + lock) + หน้า checkout
 │               └─ O6 หน้าออเดอร์ + ยกเลิก + ประวัติในหน้าสมาชิก
 │                   └─ O7 OrderSeeder (~60 ออเดอร์) + "ขายดี" บนหน้าแรก
 └──────────────────────── O8 Checkpoint
```

ทุก task เป็น vertical slice: schema/logic + หน้าจอ + เทส ใน commit เดียว

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| เงิน | เก็บ `decimal(10,2)`, คำนวณเป็นจำนวนเต็มหน่วยสตางค์ (`Money::toSatang` / `fromSatang`) | ไม่มีปัญหา float และไม่ต้องพึ่ง ext-bcmath |
| ลำดับกฎส่วนลด | class list ใน `config('roastnotes.discount_rules')` resolve ผ่าน container | promotions/loyalty แค่เพิ่มชื่อคลาส |
| เลขออเดอร์ | `RN-YYYYMMDD-NNNN` นับจากออเดอร์ของวันนั้น ภายใน transaction + unique index (retry 1 ครั้งถ้าชน) | อ่านง่าย ไม่ซ้ำ |
| กันสต็อกติดลบ | `lockForUpdate()` แถว variant ที่อยู่ในตะกร้า แล้วเช็กก่อน decrement | สั่งพร้อมกันได้ถูกต้อง |
| ออเดอร์ของคนอื่น | Policy คืน `Response::denyAsNotFound()` | ไม่เปิดเผยว่ามีเลขนั้นอยู่ |
| กลับหน้าสินค้าหลังล็อกอิน | ปุ่มของ guest ลิงก์ไป `login` พร้อม `url.intended` = หน้าสินค้า | ใช้กลไกของ Laravel |
| badge ตะกร้า | View composer ที่ `layouts.navigation` (1 query `sum(quantity)`) | ไม่ต้องส่งจากทุก controller |
| จำนวนในตะกร้า | ฟอร์มปกติ + Alpine ปุ่ม −/+ ที่ submit อัตโนมัติ | ทำงานได้แม้ปิด JS |
| ออเดอร์ตัวอย่าง | สร้างผ่าน model ตรง ๆ ด้วย Randomizer seed คงที่ ไม่เรียก PlaceOrder | ไม่ตัดสต็อก, ย้อนวันที่ได้ |
| ขายดี | `withSum` จาก order_items ที่ order ไม่ cancelled | 1 query |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| ปัดเศษส่วนลด % | ปัดลงเป็นสตางค์ในกฎแต่ละตัว (ไม่ลดเกินจริง) |
| ตัวเลข Cart กับ Checkout ไม่ตรงกัน | ทั้งสองหน้าใช้ `Quote` เดียวกัน + เทสเทียบ |
| ราคาเปลี่ยนระหว่างอยู่ในตะกร้า | Checkout คำนวณใหม่เสมอ; หน้า checkout แสดงราคาล่าสุดก่อนยืนยัน |
| `lockForUpdate` บน SQLite ไม่มีผล | เทสรันบน MySQL ของ Sail ตามเดิม |
| สถานะ `shipped` กับ `in_transit` สับสน | label ไทยชัดเจน + จดการตีความไว้ใน spec |

## Verification checkpoints

- หลัง O3–O4: ใส่ตะกร้าและแก้ไขด้วยมือบนเดสก์ท็อป + มือถือ
- หลัง O5: สั่งซื้อจริงจนสต็อกหมด, ลองสั่งเกินสต็อก
- O8: Success Criteria 1–7 + screenshot
