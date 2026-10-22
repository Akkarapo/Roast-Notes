# Spec: promotions

> โมดูลที่ 5 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **รออนุมัติ** · Depends on: ordering (pricing pipeline), catalog · ผู้ใช้ต่อ: admin (จัดการโปรโมชั่น), sales-report

## Objective

Extra Features 2 ข้อของโจทย์ในโมดูลเดียว:
- **Promotion (Discount) — coupons:** ลด % เฉพาะสินค้าที่ร่วมรายการ เช่น `LIGHTROAST15` ลด 15% เฉพาะเมล็ดคั่วอ่อนที่ร่วมรายการ
- **Promotion (Buy X Get Y):** เช่น ซื้อดริปแบ็ก 10 ซองแถม 2

ทั้งสองแบบเสียบเข้า pricing pipeline ที่ `ordering` เตรียมไว้ ตามลำดับที่ตกลงไว้: **Buy X Get Y → คูปอง → ระดับสมาชิก** และบันทึกลง `order_discounts` ของออเดอร์

**User stories**
- ในฐานะสมาชิก ฉันกรอกโค้ดคูปองในหน้าตะกร้า เห็นส่วนลดทันทีในสรุป และส่วนลดตามไปถึง Checkout
- ในฐานะสมาชิก ใส่โค้ดผิด/หมดอายุ/ใช้ครบสิทธิ์/ไม่มีสินค้าร่วมรายการ แล้วได้ข้อความบอกเหตุผลที่ชัดเจน
- ในฐานะสมาชิก ใส่ดริปแบ็กครบเงื่อนไขแล้วได้ของแถมอัตโนมัติ ไม่ต้องกรอกอะไร และถ้าเกือบครบมีข้อความบอก "เพิ่มอีก 2 ซอง ฟรี!"
- ในฐานะผู้เยี่ยมชม ฉันเห็นป้ายโปรบนการ์ดสินค้าและหน้าสินค้าที่ร่วมรายการ

**นอกขอบเขต**: หน้าสร้าง/แก้โปรโมชั่น (`admin`), รายงานการใช้โปร (`sales-report`), ส่วนลดระดับสมาชิก (`loyalty`)

## Data — EER specialization (supertype / subtype)

ตามที่วางไว้ตั้งแต่แรก: `promotions` เป็น **supertype** แยกเป็น `coupons` กับ `buy_x_get_y` แบบ **disjoint + total** (ทุกโปรเป็นได้แบบเดียวเท่านั้น) — ใช้วิธี mapping แบบ "ตาราง supertype + ตารางต่อ subtype ที่ใช้ PK เดียวกัน"

```
                      promotions (supertype)
                 ┌──────── d ─────────┐          (disjoint, total; discriminator = type)
            coupons                buy_x_get_y
      (PK/FK promotion_id)     (PK/FK promotion_id)

promotions >──< products   ผ่าน promotion_products  ("สินค้าที่ร่วมรายการ")
promotions 1 ──< order_discounts (promotion_id FK NULL — เพิ่มคอลัมน์ให้ตารางของ ordering)
```

**promotions**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| type | enum('coupon','buy_x_get_y') | discriminator |
| name | varchar(100) | เช่น "คั่วอ่อนลด 15%" |
| description | varchar(255) NULL | |
| starts_at | datetime | |
| ends_at | datetime NULL | NULL = ไม่มีวันหมด |
| is_active | boolean default true | ปิดชั่วคราวได้ |
| timestamps | | |

**coupons** (subtype)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| promotion_id | PK + FK → promotions, cascade | |
| code | varchar(30) UNIQUE | เก็บตัวพิมพ์ใหญ่, ผู้ใช้พิมพ์เล็กได้ |
| percent_off | tinyint unsigned | 1–100 |
| min_subtotal | decimal(10,2) NULL | ยอดขั้นต่ำของ**สินค้าที่ร่วมรายการ** |
| usage_limit | int unsigned NULL | ใช้ได้ทั้งหมดกี่ครั้ง (NULL = ไม่จำกัด) |
| per_user_limit | smallint unsigned NULL | ต่อสมาชิกกี่ครั้ง |

**buy_x_get_y** (subtype)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| promotion_id | PK + FK → promotions, cascade | |
| buy_quantity | smallint unsigned | X |
| free_quantity | smallint unsigned | Y |

**promotion_products** — M:N `(promotion_id, product_id)` PK คู่ · โปรต้องมีสินค้าร่วมรายการอย่างน้อย 1 ตัว (ตรวจตอนสร้างใน admin)

**order_discounts** (ของ ordering) — เพิ่ม `promotion_id` FK → promotions NULL, `nullOnDelete` ใน migration ของโมดูลนี้

**การนับจำนวนครั้งที่ใช้คูปอง**: นับจาก `order_discounts.promotion_id` ของออเดอร์ที่ไม่ถูกยกเลิก — ไม่ต้องมีตาราง redemption แยก และยกเลิกออเดอร์ = คืนสิทธิ์อัตโนมัติ

## กติกาการคิดส่วนลด

### Buy X Get Y (กฎตัวแรกใน pipeline)
- นับ**จำนวนหน่วย**ของสินค้าที่ร่วมรายการในตะกร้า (ทุก variant/ทุกการบดรวมกัน)
- ทุก ๆ **X + Y หน่วย** → **Y หน่วยที่ถูกที่สุดฟรี** (ทำซ้ำได้: 24 ซอง = ฟรี 4)
- เกือบครบ (มี ≥ X แต่ยังไม่ครบ X + Y ในรอบนั้น) → ข้อความในตะกร้า "เพิ่มอีก n ซอง รับฟรี n ซอง!"
- ป้ายในสรุป: "ซื้อ 10 แถม 2 (ดริปแบ็ก) −฿150"

### คูปอง % (กฎตัวที่สอง) — 1 คูปองต่อออเดอร์ (ตัดสินใจไว้แล้ว)
- ฐานที่ใช้ลด = ยอดของบรรทัดที่ร่วมรายการ **หักส่วนที่ได้ฟรีจาก Buy X Get Y แล้ว** (ไม่ลดซ้อนบนของแถม)
- ส่วนลด = ฐาน × percent_off ÷ 100 **ปัดลง**เป็นสตางค์
- ตรวจตามลำดับ แล้วบอกเหตุผลตัวแรกที่ไม่ผ่าน:
  1. ไม่พบโค้ด / โปรถูกปิด → "ไม่พบคูปองนี้"
  2. ยังไม่ถึง starts_at / เลย ends_at → "คูปองนี้หมดอายุแล้ว" / "คูปองนี้ยังไม่เริ่มใช้"
  3. ครบ usage_limit → "คูปองนี้ถูกใช้ครบแล้ว"
  4. สมาชิกใช้ครบ per_user_limit → "คุณใช้คูปองนี้ครบสิทธิ์แล้ว"
  5. ไม่มีสินค้าร่วมรายการในตะกร้า → "ยังไม่มีสินค้าที่ร่วมรายการในตะกร้า" (+ ลิงก์ไปดูสินค้าร่วมรายการ)
  6. ยอดสินค้าร่วมรายการไม่ถึง min_subtotal → "ซื้อสินค้าร่วมรายการเพิ่มอีก ฿x เพื่อใช้คูปอง"

### เปลี่ยนสัญญา pipeline (เพิ่มอย่างเดียว ไม่กระทบของเดิม)
- `PricingContext` เพิ่ม `appliedDiscounts` (list ของ `DiscountLine` จากกฎก่อนหน้า) เพื่อให้คูปองรู้ว่าบรรทัดไหนได้ของแถมไปเท่าไร
- `DiscountLine::meta` เก็บ `promotion_id` และ `lines` (cart_item_id → สตางค์ที่ลดในบรรทัดนั้น)
- `PlaceOrder` บันทึก `promotion_id` จาก meta ลง `order_discounts`

## หน้าจอ

| Route | ทำอะไร | สิทธิ์ |
|---|---|---|
| `POST /cart/coupon` | ใส่คูปอง (เก็บโค้ดใน session) | member |
| `DELETE /cart/coupon` | เอาคูปองออก | member |

- **หน้าตะกร้า**: ใต้กล่องสรุปมีช่อง "โค้ดส่วนลด" + ปุ่ม "ใช้โค้ด" · ใช้แล้วแสดงชิป `LIGHTROAST15 ✕` · ข้อความ error ตามรายการด้านบน · ข้อความชวนเพิ่มของแถมใต้บรรทัดดริปแบ็ก
- **กล่องสรุป (ตะกร้า + Checkout)**: บรรทัดส่วนลดแยกตามโปร (component เดิมรองรับแล้ว)
- **Checkout**: แสดงคูปองที่ใช้ (แก้ได้ที่ตะกร้า) · ถ้าคูปองใช้ไม่ได้แล้วตอนกดยืนยัน (หมดอายุระหว่างนั้น/สิทธิ์เต็ม) → ไม่สร้างออเดอร์ กลับไปตะกร้าพร้อมเหตุผล
- **การ์ดสินค้า + หน้าสินค้า**: ป้ายโปรของสินค้าที่ร่วมรายการและยังใช้ได้ เช่น ริบบิ้น "ลด 15% · LIGHTROAST15" หรือ "ซื้อ 10 แถม 2"
- **หน้า Products**: ตัวกรองใหม่ "มีโปรโมชั่น"
- **หน้าออเดอร์**: บรรทัดส่วนลดตามจริง (มีอยู่แล้ว)

## ข้อมูลตัวอย่าง (PromotionSeeder)

| โปร | ชนิด | รายละเอียด |
|---|---|---|
| `LIGHTROAST15` | คูปอง 15% | เมล็ด **คั่วอ่อน** ทุกตัว · 60 วันนับจากวันนี้ · ใช้ได้ 100 ครั้ง · คนละ 1 ครั้ง |
| `ROASTER50` | คูปอง 10% | ทุกเมล็ด · ขั้นต่ำ ฿500 · ไม่จำกัดครั้ง · คนละ 3 ครั้ง |
| `SUMMER20` | คูปอง 20% | **หมดอายุแล้ว** (ไว้ demo ข้อความ error) |
| ดริปแบ็ก 10 แถม 2 | Buy X Get Y | ดริปแบ็กทุกตัว · ไม่มีวันหมด |

ออเดอร์ตัวอย่างเดิม (OrderSeeder) ไม่เปลี่ยน — ไม่ย้อนใส่ส่วนลดให้ของเก่า

## Code Style

```php
// app/Support/Pricing/Rules/BuyXGetYRule.php
public function apply(PricingContext $context): array
{
    return BuyXGetY::query()->currentlyActive()->with('promotion.products')->get()
        ->map(fn (BuyXGetY $deal) => $deal->discountFor($context))   // ?DiscountLine
        ->filter()->values()->all();
}
```

- กฎแต่ละตัวอยู่ที่ `app/Support/Pricing/Rules/` และลงทะเบียนใน `config('roastnotes.discount_rules')`
- การตรวจคูปองอยู่ที่ `CouponValidator` (คืน `CouponCheck` = ผ่าน หรือ เหตุผล) ใช้ทั้งตอนใส่โค้ด, ตอนแสดงตะกร้า และใน `PlaceOrder`
- `Promotion::scopeCurrentlyActive()` (is_active + อยู่ในช่วงเวลา) ใช้ร่วมกับป้ายบนการ์ด

## Testing Strategy

`tests/Feature/Promotions/` + `tests/Unit/Promotions/`

| เทส | ตรวจอะไร |
|---|---|
| SchemaTest | subtype ใช้ PK เดียวกับ supertype · code unique · ลบ promotion → subtype + pivot หายตาม, order_discounts.promotion_id เป็น NULL |
| BuyXGetYRuleTest | 10 ซอง = ไม่ฟรี · 12 = ฟรี 2 · 24 = ฟรี 4 · ฟรีตัวถูกสุดเมื่อคละราคา · สินค้าไม่ร่วมรายการไม่นับ · โปรหมดอายุ/ปิด = ไม่ทำงาน · ข้อความชวนเพิ่ม |
| CouponRuleTest | ลด % เฉพาะบรรทัดร่วมรายการ · ปัดลงเป็นสตางค์ · ไม่ลดซ้อนส่วนที่ได้ฟรี · min_subtotal |
| CouponValidatorTest | เหตุผลครบทั้ง 6 แบบ · นับสิทธิ์จากออเดอร์ที่ไม่ยกเลิก · ยกเลิกออเดอร์แล้วได้สิทธิ์คืน · พิมพ์เล็กได้ |
| CartCouponTest | ใส่/เอาออกคูปองในตะกร้า · ส่วนลดตรงกันทั้งตะกร้าและ Checkout · ใส่คูปองที่สองแทนที่อันแรก |
| CheckoutPromotionTest | ออเดอร์มี order_discounts พร้อม promotion_id · คูปองหมดสิทธิ์ระหว่างทาง = ไม่สร้างออเดอร์ · ลำดับ Buy X Get Y → คูปอง |
| PromoBadgeTest | ป้ายบนการ์ด/หน้าสินค้าเฉพาะสินค้าร่วมรายการและโปรที่ยังใช้ได้ · ตัวกรอง "มีโปรโมชั่น" · query count การ์ดยังคงที่ |

## Boundaries

- **Always:** ตรวจคูปองซ้ำใน transaction ตอนยืนยันออเดอร์ (lock แถว promotion ก่อนนับสิทธิ์) · ส่วนลดปัดลง · คิดเงินเป็นสตางค์
- **Ask first:** ให้คูปองหลายใบต่อออเดอร์ · ให้ส่วนลดซ้อนบนของแถม · คูปองแบบลดเป็นจำนวนเงิน (฿) แทน %
- **Never:** เชื่อส่วนลดจากฟอร์ม · ใช้ตาราง promotions ตารางเดียวมีคอลัมน์ NULL เยอะแทน subtype (ต้องโชว์ EER specialization)

## Success Criteria

1. ใส่ `LIGHTROAST15` ในตะกร้าที่มีเมล็ดคั่วอ่อน → ลด 15% เฉพาะบรรทัดคั่วอ่อน ตัวเลขตรงกันทั้งตะกร้า, Checkout และออเดอร์
2. ใส่ดริปแบ็ก 12 ซอง → ฟรี 2 ซองถูกสุดอัตโนมัติ; 10 ซอง → ข้อความชวนเพิ่ม 2 ซอง
3. โค้ดที่ใช้ไม่ได้ได้ข้อความบอกเหตุผลถูกต้องทั้ง 6 แบบ
4. ใช้ครบสิทธิ์แล้วใช้ซ้ำไม่ได้ · ยกเลิกออเดอร์แล้วได้สิทธิ์คืน
5. `order_discounts` ของออเดอร์ใหม่มี `promotion_id`
6. ป้ายโปรบนการ์ด/หน้าสินค้า + ตัวกรอง "มีโปรโมชั่น" ทำงาน, query คงที่
7. `sail artisan test` ผ่าน, `sail pint --test` สะอาด

## Open Questions

1. **ซื้อ 10 แถม 2 แบบไหน?** (ก) ลูกค้าใส่ 12 ซองเอง แล้ว 2 ซองถูกสุดฟรี + ข้อความชวนเมื่อมี 10–11 ซอง *(เสนอ — ทำได้ตรงไปตรงมา, สต็อกถูกต้อง)* หรือ (ข) ใส่ 10 ซองแล้วระบบเพิ่ม 2 ซองฟรีให้เอง
2. **สิทธิ์คูปอง**: LIGHTROAST15 ใช้ได้ 100 ครั้ง คนละ 1 ครั้ง — โอเคไหม?
3. **ไม่ลดซ้อน**: บรรทัดที่ได้ของแถมแล้ว คูปองลดเฉพาะส่วนที่ต้องจ่าย — โอเคไหม?
4. **ที่กรอกโค้ด**: หน้าตะกร้า (Checkout แสดงอย่างเดียว) — หรืออยากให้กรอกที่ Checkout ด้วย?
5. **โปรตัวอย่าง** 4 ตัวตามตาราง — อยากเพิ่ม/เปลี่ยนไหม?
