# Plan: promotions

> อ้างอิง [docs/specs/SPEC-promotions.md](../docs/specs/SPEC-promotions.md) · สถานะ: **เสร็จแล้ว**

## ลำดับ

```
P1 Schema (supertype/subtype + pivot + order_discounts.promotion_id) + models + factories
 ├─ P2 ขยาย pipeline: PricingContext.appliedDiscounts, DiscountLine meta → PlaceOrder บันทึก promotion_id
 │   ├─ P3 BuyXGetYRule + ข้อความชวนเพิ่ม
 │   └─ P4 CouponValidator + CouponRule
 │       └─ P5 คูปองในตะกร้า (session) + Checkout ตรวจซ้ำใน transaction
 └─ P6 ป้ายโปรบนการ์ด/หน้าสินค้า + ตัวกรอง "มีโปรโมชั่น" + PromotionSeeder
     └─ P7 Checkpoint
```

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| Subtype | ตารางลูกใช้ `promotion_id` เป็น PK+FK, model `Coupon`/`BuyXGetY` belongsTo `Promotion`, `Promotion` hasOne ทั้งสอง | mapping EER แบบ class-table, query ตรงไปตรงมา |
| เก็บคูปองที่ใส่ | `session('coupon_code')` | ไม่ต้องเพิ่มคอลัมน์ใน carts; หายเมื่อสั่งซื้อสำเร็จ |
| นับสิทธิ์ | `order_discounts` join orders ที่ไม่ cancelled | ไม่มีตาราง redemption; ยกเลิก = คืนสิทธิ์ |
| กันใช้เกินสิทธิ์พร้อมกัน | `lockForUpdate` แถว promotion ใน PlaceOrder ก่อนนับ | สองออเดอร์พร้อมกันไม่เกิน usage_limit |
| ฟรีตัวถูกสุด | แตกบรรทัดเป็นหน่วย (≤ 20 × จำนวนบรรทัด) เรียงราคา แล้วเลือก Y × รอบ ตัวแรก | ง่าย, ข้อมูลเล็ก |
| ป้ายบนการ์ด | โหลดโปรที่ active ทั้งหมด 1 query ต่อหน้า (`PromotionBadges` เก็บ map product_id → ป้าย) | ไม่เพิ่ม query ตามจำนวนการ์ด |
| ตัวกรอง "มีโปรโมชั่น" | `whereHas('promotions', currentlyActive)` | ใช้ scope เดิมต่อได้ |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| เปลี่ยน `PricingContext` แล้วเทส ordering พัง | เพิ่ม field แบบมีค่าเริ่มต้น; รันเทส ordering ทุก task |
| เวลา starts/ends กับ timezone | ใช้ `now()` ตาม APP_TIMEZONE (Asia/Bangkok) ทั้งหมด |
| ข้อความชวนเพิ่มแสดงผิดเมื่อมีหลายโปร Buy X Get Y | คำนวณต่อโปร แสดงทีละโปร |

## Checkpoints
- หลัง P3–P4: เทียบตัวเลขกับการคิดมือ 3 กรณี (12 ซอง, คั่วอ่อน + คูปอง, ปนกัน)
- หลัง P5: ลองใส่โค้ดทั้ง 4 ตัว + โค้ดมั่ว ในเบราว์เซอร์
- P7: Success Criteria 1–7 + screenshot
