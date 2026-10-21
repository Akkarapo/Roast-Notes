# Spec: ordering

> โมดูลที่ 3 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **อนุมัติแล้ว (2026-10-21)** · Depends on: catalog · ผู้ใช้ต่อ: promotions, loyalty, community, admin, sales-report

## Objective

ปิด Core Features ที่เหลือของโจทย์ทั้งหมด: **Cart Page**, **Summary / Checkout Page**, การยืนยันออเดอร์ และประวัติสั่งซื้อใน **Member Info Page** — พร้อมวาง **pricing pipeline** ที่ `promotions` และ `loyalty` จะมาเสียบส่วนลดภายหลัง

**User stories**
- ในฐานะสมาชิก ฉันเลือกขนาดถุง การบด (เมล็ดเต็ม / บดดริป / บดเอสเปรสโซ) และจำนวน แล้วใส่ตะกร้าจากหน้าสินค้าได้
- ในฐานะผู้เยี่ยมชม กดใส่ตะกร้าแล้วถูกพาไปล็อกอิน และกลับมาหน้าสินค้าเดิมหลังล็อกอิน
- ในฐานะสมาชิก ฉันแก้จำนวน/การบด และลบรายการในตะกร้าได้ เห็นยอดรวมทันที
- ในฐานะสมาชิก ฉันกด Checkout เห็นสรุปรายการ ส่วนลดทุกประเภท ค่าส่ง ยอดสุทธิ กรอก/แก้ที่อยู่จัดส่ง แล้วยืนยันออเดอร์ได้
- ในฐานะสมาชิก ฉันเห็นประวัติสั่งซื้อในหน้าข้อมูลสมาชิก และเปิดดูรายละเอียดแต่ละออเดอร์ได้
- ในฐานะผู้เยี่ยมชม หน้าแรกแสดง "ขายดี" 4 อันดับ

**นอกขอบเขต**: กฎส่วนลดจริง (`promotions`, `loyalty`), การเปลี่ยนสถานะออเดอร์โดยแอดมิน (`admin`), รีวิวจากรายการที่ซื้อ (`community`), การจ่ายเงินจริง

## Data (โมดูลนี้เป็นเจ้าของ)

```
users 1 ──── 1 carts 1 ──< cart_items >── 1 product_variants
users 1 ──< orders 1 ──< order_items >── 1 product_variants
                  1 ──< order_discounts
```

**carts** — ตะกร้าเดียวต่อสมาชิก

| คอลัมน์ | ชนิด |
|---|---|
| id | bigint PK |
| user_id | FK → users UNIQUE, cascade |
| timestamps | |

**cart_items**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| cart_id | FK → carts, cascade | |
| product_variant_id | FK → product_variants, cascade | อ้างอิง variant ไม่ใช่ product (ตามแผนเดิม) |
| grind | enum('whole_bean','drip','espresso','none') | ดริปแบ็ก = `none` เสมอ |
| quantity | smallint unsigned | 1–20 และไม่เกินสต็อก |
| UNIQUE(cart_id, product_variant_id, grind) | | ใส่ซ้ำ = บวกจำนวนในบรรทัดเดิม |

ราคาในตะกร้าอ่านจาก variant ปัจจุบันเสมอ (ไม่ snapshot)

**orders**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| order_number | varchar(20) UNIQUE | `RN-20261021-0007` (วันที่ + ลำดับของวัน) |
| user_id | FK → users, restrict | |
| status | enum('pending','confirmed','shipped','in_transit','delivered','cancelled') | เริ่มที่ `pending` — ดูลำดับสถานะด้านล่าง |
| subtotal | decimal(10,2) | ผลรวม line_total |
| discount_total | decimal(10,2) | ผลรวม order_discounts |
| shipping_fee | decimal(10,2) | |
| total | decimal(10,2) | subtotal − discount_total + shipping_fee |
| recipient_name, phone | varchar | snapshot ตอนสั่ง |
| shipping_address | text | snapshot ตอนสั่ง |
| note | varchar(500) NULL | |
| placed_at | datetime | |
| cancelled_at | datetime NULL | |
| timestamps | | |

**order_items** — snapshot ทุกอย่างที่ใช้คิดเงิน เพื่อให้ออเดอร์เก่าไม่เปลี่ยนเมื่อราคา/ชื่อสินค้าเปลี่ยน

| คอลัมน์ | ชนิด |
|---|---|
| id | bigint PK |
| order_id | FK → orders, cascade |
| product_variant_id | FK → product_variants, restrict |
| product_name | varchar(160) |
| size | varchar(10) |
| grind | enum เดียวกับ cart_items |
| unit_price | decimal(10,2) |
| quantity | smallint unsigned |
| line_total | decimal(10,2) |

`community` จะใช้ `order_items.id` เป็นหลักฐานการซื้อสำหรับรีวิว (ตัดสินใจไว้แล้ว)

**order_discounts** — ทุกส่วนลดที่ใช้ในออเดอร์ (หนึ่งแถวต่อกฎ) เพื่อให้สรุป "ส่วนลดทุกประเภท" ย้อนหลังได้

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| order_id | FK → orders, cascade | |
| source | varchar(30) | เช่น `coupon`, `buy_x_get_y`, `tier` |
| label | varchar(120) | ข้อความที่แสดง เช่น "LIGHTROAST15 ลด 15%" |
| amount | decimal(10,2) | ค่าบวก |

`promotions` จะเพิ่ม `promotion_id` (FK NULL) ใน migration ของตัวเอง

## Pricing pipeline (สัญญาที่โมดูลอื่นใช้)

```php
interface DiscountRule
{
    /** @return list<DiscountLine> ส่วนลดที่กฎนี้ให้ได้กับตะกร้า (ว่าง = ไม่เข้าเงื่อนไข) */
    public function apply(PricingContext $context): array;
}

final class PricingContext  // อ่านอย่างเดียว
{
    public User $user;
    public Collection $lines;        // CartLine: variant, product, grind, quantity, unitPrice, lineTotal (สตางค์)
    public int $subtotal;            // หน่วยสตางค์ทั้งหมด
    public int $discountedSoFar;     // ยอดลดจากกฎก่อนหน้า (ให้ loyalty คิดจากยอดที่เหลือ)
    public ?string $couponCode;      // promotions ใช้
}

final class DiscountLine { string $source; string $label; int $amount; }  // amount เป็นสตางค์

final class Quote { lines, subtotal, discounts (list<DiscountLine>), discountTotal, shippingFee, total }
```

- `PriceCalculator::quote(Cart $cart, ?string $couponCode = null): Quote` เรียกกฎตามลำดับใน `config('roastnotes.discount_rules')` (ตอนนี้ว่าง) — ลำดับตามที่ตกลงไว้: Buy X Get Y → คูปอง → ระดับสมาชิก
- ส่วนลดรวมไม่เกิน subtotal, เงินคิดเป็นจำนวนเต็มหน่วยสตางค์ ไม่ใช้ float
- หน้า Cart และ Checkout ใช้ `Quote` ตัวเดียวกัน ตัวเลขจึงตรงกันเสมอ

**ค่าส่ง**: ฿50 ต่อออเดอร์ · ส่งฟรีเมื่อยอดหลังหักส่วนลด ≥ ฿1,000 — ค่าอยู่ใน `config/roastnotes.php`

## การยืนยันออเดอร์ (POST /checkout)

ทำใน DB transaction เดียว:
1. `lockForUpdate` variants ทุกตัวในตะกร้า → ถ้าสต็อกไม่พอแม้แต่บรรทัดเดียว ยกเลิกทั้งหมด กลับไปตะกร้าพร้อมบอกว่าบรรทัดไหนเหลือเท่าไร
2. คำนวณ `Quote` ใหม่ (ไม่เชื่อตัวเลขจากฟอร์ม)
3. สร้าง orders + order_items (snapshot) + order_discounts
4. ตัดสต็อก, ล้างตะกร้า
5. redirect ไปหน้าออเดอร์พร้อมข้อความ "สั่งซื้อสำเร็จ"

ตะกร้าว่างหรือมีสินค้าที่ถูกปิดขาย (`is_active = false`) → ไม่ให้ยืนยัน

**ยกเลิกออเดอร์**: สมาชิกยกเลิกเองได้เฉพาะสถานะ `pending` → คืนสต็อก, `cancelled_at = now()`

**ลำดับสถานะ** (`OrderStatus` enum มี `next()` และ `canTransitionTo()` ให้ `admin` ใช้)

```
pending ──> confirmed ──> shipped ──> in_transit ──> delivered
รอยืนยัน     ยืนยันแล้ว     ส่งพัสดุแล้ว    อยู่ระหว่างจัดส่ง    ได้รับแล้ว
   │
   └──> cancelled (ยกเลิก) — สมาชิกทำได้ตอน pending, แอดมินทำได้ก่อน shipped
```
รีวิว (`community`) เปิดให้เขียนได้เมื่อออเดอร์เป็น `delivered` เท่านั้น — `OrderStatus::allowsReview()`

## หน้าจอ

| Route | หน้า | สิทธิ์ |
|---|---|---|
| `POST /cart/items` | ใส่ตะกร้า (จากหน้าสินค้า) | member |
| `GET /cart` | ตะกร้า | member |
| `PATCH /cart/items/{item}` | แก้จำนวน/การบด | เจ้าของตะกร้า |
| `DELETE /cart/items/{item}` | ลบบรรทัด | เจ้าของตะกร้า |
| `GET /checkout` | สรุป + ที่อยู่จัดส่ง | member, ตะกร้าไม่ว่าง |
| `POST /checkout` | ยืนยันออเดอร์ | member |
| `GET /orders/{order:order_number}` | รายละเอียดออเดอร์ | เจ้าของเท่านั้น (อื่น = 404) |
| `POST /orders/{order}/cancel` | ยกเลิก (pending) | เจ้าของ |

### หน้าสินค้า — ฟอร์มใส่ตะกร้า (แทนปุ่มชั่วคราวของ catalog)
```
ขนาดถุง: (100g ฿230) (250g ฿510) (500g ฿940)
การบด:   (เมล็ดเต็ม) (บดดริป) (บดเอสเปรสโซ)        ← ไม่แสดงสำหรับดริปแบ็ก
จำนวน:   [ − ]  1  [ + ]
[ ใส่ตะกร้า ]       → กลับหน้าเดิม + แถบแจ้ง "ใส่ตะกร้าแล้ว · ดูตะกร้า ›"
```

### `/cart`
```
ตะกร้า  your bag
┌───────────────────────────────────────────┐  ┌ สรุป ─────────────────┐
│ [ภาพ] Chiang Rai Caturra · 250g            │  │ ยอดสินค้า      ฿1,020 │
│       การบด [บดดริป ▾]  [−] 2 [+]   ฿1,020 │  │ ส่วนลด            —   │ ← แต่ละ DiscountLine
│       ลบ                                   │  │ ค่าส่ง            ฿50 │
├───────────────────────────────────────────┤  │ ─────────────────────│
│ ...                                        │  │ ยอดสุทธิ  ฿1,070 (ลายมือ)│
└───────────────────────────────────────────┘  │ [ ไปชำระเงิน → ]      │
                                                │ อีก ฿x ส่งฟรี          │
                                                └───────────────────────┘
```
- จำนวนเกินสต็อก → แจ้งในบรรทัด และปุ่มไปชำระเงิน disabled
- ตะกร้าว่าง → empty state เดิม
- ไอคอนตะกร้าใน navbar มีตัวเลขจำนวนชิ้น

### `/checkout`
ซ้าย: ฟอร์มที่อยู่ (ชื่อผู้รับ, เบอร์โทร, ที่อยู่ — เติมจากโปรไฟล์ให้ก่อน, หมายเหตุ) · ขวา: สรุปรายการแบบย่อ + ส่วนลด + ค่าส่ง + ยอดสุทธิ + `[ ยืนยันคำสั่งซื้อ ]`
(ช่องกรอกคูปองจะเพิ่มโดย `promotions` — ตอนนี้ยังไม่มี)

### `/orders/{order_number}` — ใบสรุปออเดอร์
เลขออเดอร์ (ลายมือ), วันที่, ป้ายสถานะ, ที่อยู่จัดส่ง, ตารางรายการ (snapshot), ส่วนลด, ค่าส่ง, ยอดสุทธิ, ปุ่มยกเลิก (ถ้า pending)

### หน้าข้อมูลสมาชิก
เพิ่มการ์ด "ประวัติการสั่งซื้อ" บนสุด: ตารางเลขออเดอร์ · วันที่ · สถานะ · จำนวนชิ้น · ยอดสุทธิ · ลิงก์ (ล่าสุดก่อน, 10 รายการ + ดูทั้งหมด)

### หน้าแรก — "ขายดี"
4 สินค้า active ที่ขายได้จำนวนชิ้นมากที่สุดจาก order_items ของออเดอร์ที่ไม่ถูกยกเลิก · ถ้ายังไม่มียอดขายให้ซ่อนส่วนนี้

## ข้อมูลตัวอย่าง

`OrderSeeder` สร้างออเดอร์ย้อนหลัง ~60 ออเดอร์ใน 90 วัน ของ member1–3 (สุ่มแบบกำหนด seed; ออเดอร์เก่ากว่า 7 วันส่วนใหญ่ `delivered`, ใหม่กว่านั้นคละ `pending`…`in_transit`, ~5% `cancelled`) เพื่อให้ "ขายดี" มีข้อมูล และโมดูล `sales-report` / `loyalty` (ยอดซื้อสะสม) มีตัวเลขให้ทดสอบ — ไม่ตัดสต็อกจริงของ seed

## Code Style

```php
// app/Http/Controllers/CheckoutController.php
public function store(CheckoutRequest $request, PlaceOrder $placeOrder): RedirectResponse
{
    $order = $placeOrder($request->user(), $request->shippingDetails());

    return redirect()->route('orders.show', $order)->with('status', __('Order placed'));
}

// app/Actions/PlaceOrder.php — ทุกขั้นอยู่ใน DB::transaction, throws OutOfStock
```

- Logic ธุรกิจอยู่ใน `app/Actions` (PlaceOrder, AddToCart, CancelOrder) และ `app/Support/Pricing` — controller บาง
- Authorization ด้วย Policy (`CartItemPolicy`, `OrderPolicy`) — ออเดอร์ของคนอื่นตอบ 404 ไม่ใช่ 403 เพื่อไม่บอกว่ามีอยู่
- Enum ใหม่: `Grind` (label ไทย), `OrderStatus` (label ไทย + สีป้าย)

## Testing Strategy

`tests/Feature/Ordering/` + `tests/Unit/Ordering/`

| เทส | ตรวจอะไร |
|---|---|
| AddToCartTest | guest → login แล้วกลับหน้าเดิม · ใส่ซ้ำรวมบรรทัด · grind ต่างกัน = คนละบรรทัด · ดริปแบ็กบังคับ grind `none` · เกินสต็อก/สินค้า inactive ถูกปฏิเสธ · จำนวน 1–20 |
| CartTest | แก้จำนวน/การบด · ลบ · ตะกร้าคนอื่นแก้ไม่ได้ · ยอดรวมถูก · navbar badge |
| PriceCalculatorTest (unit) | subtotal · ค่าส่ง ฿50 / ฟรีเมื่อ ≥ ฿1,000 · กฎส่วนลดปลอมถูกเรียกตามลำดับและส่ง discountedSoFar ถูก · ส่วนลดรวมไม่เกิน subtotal |
| CheckoutTest | ตะกร้าว่างเข้าไม่ได้ · เติมที่อยู่จากโปรไฟล์ · ยืนยันแล้วได้ order + items snapshot + discounts · ตัดสต็อก · ล้างตะกร้า · สต็อกไม่พอ = ไม่สร้างอะไรเลย · ราคาเปลี่ยนหลังสั่ง ออเดอร์เดิมไม่เปลี่ยน · order_number ไม่ซ้ำ |
| OrderTest | เจ้าของเห็น · คนอื่น 404 · ยกเลิก pending คืนสต็อก · ยกเลิกสถานะอื่นไม่ได้ · ประวัติในหน้าสมาชิกเรียงล่าสุดก่อน |
| OrderStatusTest (unit) | ลำดับ next() · canTransitionTo() · allowsReview() เฉพาะ delivered · label ไทย |
| OrderSeederTest | ~60 ออเดอร์ใน 90 วัน · ทุกออเดอร์ยอดรวม = items − discounts + ค่าส่ง · seed ซ้ำได้ผลเดิม · ไม่แตะสต็อก |
| BestSellersTest | เรียงตามจำนวนชิ้น · ไม่นับออเดอร์ยกเลิก · ไม่แสดงสินค้า inactive · ไม่มียอดขาย = ซ่อนส่วน |

## Boundaries

- **Always:** คิดเงินฝั่งเซิร์ฟเวอร์ทุกครั้ง · ตัดสต็อกใน transaction พร้อม lock · snapshot ราคา/ชื่อใน order_items · ใช้ทศนิยมแบบสตริง ไม่ใช้ float กับเงิน
- **Ask first:** เปลี่ยนสัญญา `DiscountRule` หลัง promotions เริ่มใช้ · เพิ่มระบบชำระเงิน · เปลี่ยนชื่อคอลัมน์ที่ community/sales-report อ้าง
- **Never:** เชื่อราคา/ยอดรวมจากฟอร์ม · ลบ order ที่มีอยู่ (ใช้สถานะ cancelled) · ให้สต็อกติดลบ

## Success Criteria

1. สมาชิกใส่ตะกร้าจากหน้าสินค้า (ขนาด + การบด + จำนวน) แก้ไข/ลบในตะกร้าได้ ตัวเลขตรงกันทุกหน้า
2. Checkout สร้างออเดอร์ `pending` พร้อม snapshot, ตัดสต็อก, ล้างตะกร้า และแสดงหน้าออเดอร์
3. สั่งพร้อมกันจนสต็อกไม่พอ → ไม่มีออเดอร์ใดทำให้สต็อกติดลบ
4. หน้าข้อมูลสมาชิกแสดงประวัติสั่งซื้อ, เปิดดูออเดอร์ของตัวเองได้, ของคนอื่นไม่ได้
5. หน้าแรกแสดง "ขายดี" จากยอดขายจริง
6. `PriceCalculator` รับกฎส่วนลดเพิ่มได้โดยไม่ต้องแก้ Cart/Checkout (พิสูจน์ด้วยเทสกฎปลอม)
7. ใช้งานบนมือถือได้, `sail artisan test` ผ่าน, `sail pint --test` สะอาด

## Decisions (2026-10-21)

1. ค่าส่ง ฿50 · ฟรีเมื่อยอดหลังหักส่วนลด ≥ ฿1,000
2. สมาชิกยกเลิกเองได้ตอน `pending` (คืนสต็อก)
3. สร้างออเดอร์ตัวอย่าง ~60 ออเดอร์ย้อนหลัง 90 วัน
4. เพิ่มสถานะ "อยู่ระหว่างจัดส่ง" (`in_transit`) → 6 สถานะ; รีวิวได้เมื่อ "ได้รับแล้ว" — ตีความว่า "ส่งพัสดุแล้ว" (ร้านส่งให้ขนส่ง) มาก่อน "อยู่ระหว่างจัดส่ง" (ขนส่งกำลังนำส่ง)
