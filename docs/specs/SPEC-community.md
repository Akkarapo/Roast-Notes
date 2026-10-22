# Spec: community

> โมดูลที่ 7 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **อนุมัติแล้ว (2026-10-23)** · Depends on: catalog, ordering (order_items = หลักฐานการซื้อ), taste-profile (`BrewMethod`) · ผู้ใช้ต่อ: admin (ลบรีวิว), sales-report

## Objective

Extra Features 3 ข้อของโจทย์:
- **Wish List** — กดหัวใจเก็บเมล็ดที่อยากลองไว้ซื้อทีหลัง
- **Reviews** — รีวิวพร้อม**วิธีชง**ที่ใช้ เพราะเมล็ดเดียวกันชงต่างวิธีรสต่างกันมาก กรองรีวิวตามวิธีชงได้
- **Rating 1–5** — คะแนนเฉลี่ยรวม และ**ค่าเฉลี่ยแยกตามวิธีชง** ("Espresso ★4.6 · Pour-over ★3.9")

**User stories**
- ในฐานะสมาชิก ฉันกดหัวใจบนการ์ด/หน้าสินค้าเพื่อเก็บเข้า Wish List และเปิดดูรวมได้ที่หน้า Wish List
- ในฐานะสมาชิกที่ได้รับของแล้ว ฉันรีวิวสินค้าแต่ละรายการในออเดอร์ได้: ให้ดาว เลือกวิธีชง เขียนความเห็น
- ในฐานะคนเลือกซื้อ ฉันเห็นดาวเฉลี่ยบนการ์ด และในหน้าสินค้าเห็นค่าเฉลี่ยแยกตามวิธีชง + กรองรีวิวเฉพาะวิธีชงของฉันได้
- ในฐานะคนเลือกซื้อ ฉันรู้ว่าทุกรีวิวมาจากคนที่ซื้อจริง (ป้าย "ซื้อแล้ว · 250g บดดริป")

**นอกขอบเขต**: ลบ/ซ่อนรีวิวโดยแอดมิน (`admin`), รายงานคะแนน (`sales-report`), แจ้งเตือนเมื่อสินค้าใน Wish List ลดราคา, รูปในรีวิว, กด like รีวิว

## Data

```
users    1 ──< wishlists >── 1 products        (M:N: PK คู่ user_id + product_id)
order_items 1 ── 0..1 reviews                   (reviews.order_item_id UNIQUE NOT NULL — ตกลงไว้ใน CAPABILITY-MAP)

ผู้รีวิว   = reviews → order_items → orders.user_id
สินค้า     = reviews → order_items → product_variants.product_id
```

reviews **ไม่เก็บ** `user_id` / `product_id` ซ้ำ: ทั้งสองค่าขึ้นกับ `order_item_id` อยู่แล้ว ถ้าเก็บจะเป็น transitive dependency (ผิด 3NF) และเสี่ยงข้อมูลไม่ตรงกัน — หาได้ด้วย JOIN

**wishlists**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| user_id | FK → users, cascade | PK คู่ |
| product_id | FK → products, cascade | PK คู่ · เก็บระดับสินค้า ไม่ใช่ขนาดถุง |
| created_at | timestamp | เรียงตามวันที่เพิ่ม |

**reviews**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| order_item_id | FK → order_items, cascade, **UNIQUE** | 1 รายการที่ซื้อ = 1 รีวิว · ซื้อซ้ำ = รีวิวได้อีก |
| rating | tinyint unsigned | 1–5 (CHECK) |
| brew_method | enum ของ `BrewMethod` 7 แบบ | บังคับเลือก · ค่าเริ่มต้น = วิธีชงจาก Taste Profile, ดริปแบ็ก = `drip_bag` |
| comment | text NULL | ไม่เกิน 1,000 ตัวอักษร |
| timestamps | | `updated_at ≠ created_at` → แสดง "(แก้ไขแล้ว)" |

index: `brew_method` (กรอง/จัดกลุ่ม) · การหา reviews ของสินค้าใช้ index เดิมของ `order_items.product_variant_id` และ `product_variants.product_id`

**ค่าเฉลี่ย**: คำนวณด้วย query (`withAvg`/`withCount`, `GROUP BY brew_method`) ไม่เก็บคอลัมน์ค่าเฉลี่ยใน products — ไม่มีข้อมูลซ้ำซ้อน

## กติกา

### รีวิว
- เขียนได้เมื่อ: order_item อยู่ในออเดอร์ของตัวเอง + ออเดอร์สถานะ **delivered** + ยังไม่เคยรีวิว order_item นี้
- ทุกอย่างผ่าน `ReviewPolicy` (create ต่อ order_item, update/delete ต่อ review) → คนอื่น = 404
- แก้ไข/ลบรีวิวของตัวเองได้ตลอด (ลบแล้วเขียนใหม่ได้)
- รีวิวแสดง: ชื่อผู้รีวิว (ชื่อต้น + อักษรแรกของนามสกุล เช่น "สมชาย ใ.") · ดาว · ป้ายวิธีชง · ป้าย "ซื้อแล้ว · 250g · บดดริป" (จาก order_item) · วันที่ · ความเห็น
- ถ้าสินค้าถูกปิดขาย (`is_active = false`) รีวิวยังอยู่ แต่เขียนใหม่ได้ตามปกติจากหน้าออเดอร์

### Rating
- การ์ดสินค้า: `★ 4.5 (12)` — ไม่มีรีวิวไม่แสดง
- หน้าสินค้า: ค่าเฉลี่ยรวม (ทศนิยม 1 ตำแหน่ง) + แท่งกระจายดาว 5→1 + **ตารางค่าเฉลี่ยตามวิธีชง** (เฉพาะวิธีที่มีรีวิว, เรียงตามจำนวนรีวิว)
- กรอง `?brew=espresso` → รายการรีวิวเหลือเฉพาะวิธีนั้น · สมาชิกที่มีวิธีชงใน Taste Profile มีชิปลัด "วิธีชงของฉัน"
- หน้า Products: ตัวเลือกเรียงใหม่ "คะแนนรีวิว" (ไม่มีรีวิวอยู่ท้าย)

### Wish List
- หัวใจบนการ์ด (มุมล่าง) และหน้าสินค้า — กดแล้วสลับเพิ่ม/เอาออก (ฟอร์ม POST ธรรมดา ทำงานได้โดยไม่ต้องมี JS; Alpine แค่ทำให้ไม่กระโดดหน้า)
- ผู้เยี่ยมชมกดหัวใจ → ไปหน้า login แล้วกลับมาหน้าเดิม
- หน้า `/wishlist`: การ์ดสินค้าแบบเดียวกับหน้า Products (match %, ป้ายโปร, ราคาเริ่ม, หมด/ล็อตหายาก) + ปุ่มเอาออก · ว่าง = empty state ชวนไปหน้า Products
- เมนูผู้ใช้: ลิงก์ "Wish List (n)"

## หน้าจอ

| Route | ทำอะไร | สิทธิ์ |
|---|---|---|
| `GET /wishlist` | รายการ Wish List | member |
| `POST /wishlist/{product}` | เพิ่ม | member |
| `DELETE /wishlist/{product}` | เอาออก | member |
| `GET /order-items/{item}/review/create` | ฟอร์มรีวิว (มีรายละเอียดรายการที่ซื้อ) | เจ้าของ + delivered |
| `POST /order-items/{item}/review` | บันทึก | เจ้าของ + delivered |
| `GET /reviews/{review}/edit` · `PATCH` · `DELETE` | แก้/ลบ | เจ้าของรีวิว |

- **หน้าออเดอร์** (delivered): ทุกบรรทัดมีปุ่ม "เขียนรีวิว" หรือ "★4 · แก้รีวิว"
- **หน้าสินค้า**: section "รีวิว" ใต้ About this lot — สรุปดาว + ตารางวิธีชง + ชิปกรอง + รายการรีวิว (หน้าละ 10) · ถ้าฉันมีรายการที่ได้รับแล้วแต่ยังไม่รีวิว → ปุ่ม "รีวิวล็อตนี้"
- **ฟอร์มรีวิว**: ดาว 5 ดวงแบบ radio (เลือกด้วยคีย์บอร์ดได้) · ชิปวิธีชง 7 แบบ (ค่าเริ่มต้นตาม Taste Profile) · textarea + ตัวนับตัวอักษร

## ข้อมูลตัวอย่าง (CommunitySeeder)

- (เพิ่ม 2026-10-23) ลูกค้าตัวอย่างอีก 15 คน (`UserSeeder::COMMUNITY`, 13 คนทำ Taste Quiz) มีออเดอร์เล็ก ๆ คนละ 3–8 ใบ จาก random stream แยก → ออเดอร์ 60 ใบของ member1–3 และระดับสมาชิกไม่เปลี่ยน · รวม 155 ออเดอร์ ~200 รีวิว เฉลี่ย ~4 รีวิวต่อสินค้า

- รีวิวประมาณ 80% ของ order_items ในออเดอร์ delivered ของ member1–3 (seed คงที่)
- ดาว = อิงคะแนน cupping (≥ 87 → มักได้ 5, < 83 → มัก 3–4) + สุ่มเล็กน้อย
- วิธีชง: ดริปแบ็ก → `drip_bag`; เมล็ด → สุ่มตามน้ำหนักที่เข้ากับระดับคั่ว (คั่วอ่อน → pour-over/AeroPress, คั่วเข้ม → espresso/moka pot) และให้บางเมล็ดมีหลายวิธีชงพอให้ตารางค่าเฉลี่ยเห็นความต่าง
- ความเห็น: ประโยคภาษาไทยจากแม่แบบ ใส่โน้ตจริงของเมล็ดนั้น เช่น "ชงเอสเปรสโซแล้วได้ช็อกโกแลตชัด บอดี้หนา" · ~20% ไม่มีความเห็น
- Wish List: สมาชิกละ 3–5 ตัว (รวมล็อตหายากบางตัว)

## Code Style

```php
// app/Models/Product.php
// app/Models/Review.php — reviews ของสินค้าหนึ่ง: JOIN ผ่าน order_items → product_variants
public function scopeForProduct(Builder $query, Product $product): void
{
    $query->whereHas('orderItem.variant', fn (Builder $q) => $q->where('product_id', $product->id));
}

// app/Models/Product.php — ค่าเฉลี่ยบนการ์ด: correlated subquery ตัวเดียว ไม่เพิ่ม query ต่อการ์ด
public function scopeWithRating(Builder $query): void
{
    $base = fn () => Review::query()->join('order_items', 'order_items.id', '=', 'reviews.order_item_id')
        ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
        ->whereColumn('product_variants.product_id', 'products.id');

    $query->addSelect([
        'rating_avg' => $base()->selectRaw('AVG(reviews.rating)'),
        'rating_count' => $base()->selectRaw('COUNT(*)'),
    ]);
}
```

- `App\Actions\SubmitReview` (สร้างจาก order_item ที่ได้จาก route ไม่รับ id อื่นจากฟอร์ม)
- `ReviewPolicy`, `StoreReviewRequest` (rating 1–5, brew_method enum, comment ≤ 1000)
- component ใหม่: `x-star-rating` (แสดง), `x-star-input` (ฟอร์ม), `x-wishlist-button`, `x-review-card`

## Testing Strategy

`tests/Feature/Community/`

| เทส | ตรวจอะไร |
|---|---|
| SchemaTest | PK คู่ wishlists · order_item_id unique · rating นอก 1–5 ถูกปฏิเสธ · ลบ order → รีวิวหาย |
| WishlistTest | เพิ่ม/เอาออก/ซ้ำไม่ error · ผู้เยี่ยมชม → login · หน้า /wishlist · นับในเมนู |
| ReviewEligibilityTest | delivered เท่านั้น (5 สถานะอื่นไม่ได้) · ออเดอร์คนอื่น = 404 · รีวิวซ้ำรายการเดิมไม่ได้ · ซื้อซ้ำรีวิวได้อีก |
| ReviewCrudTest | สร้าง/แก้/ลบ · validation · ส่ง order_item_id อื่นในฟอร์มไม่มีผล · "(แก้ไขแล้ว)" |
| RatingTest | ค่าเฉลี่ยรวม + แยกวิธีชง · การ์ดแสดง ★ · เรียงตามคะแนน · กรอง `?brew=` · query count หน้า Products คงที่ |
| CommunitySeederTest | มีรีวิวเฉพาะรายการ delivered · ทุกรีวิว unique ต่อ order_item |

## Boundaries

- **Always:** สิทธิ์รีวิวตรวจผ่าน policy ฝั่ง server · ค่าเฉลี่ยคำนวณจาก query · escape ความเห็น (Blade `{{ }}`)
- **Ask first:** ให้รีวิวได้โดยไม่ต้องซื้อ · เก็บค่าเฉลี่ยลง products · ให้แอดมินแก้ข้อความรีวิว
- **Never:** รับ order_item_id จากฟอร์ม (มาจาก route + policy เท่านั้น) · เก็บ user_id/product_id ซ้ำใน reviews · แสดงนามสกุลเต็ม/อีเมลผู้รีวิว

## Success Criteria

1. member กดหัวใจ 2 ตัว → เห็นในหน้า Wish List และเมนูแสดง (2); กดซ้ำเอาออกได้
2. ออเดอร์ delivered มีปุ่มรีวิวทุกบรรทัด; ออเดอร์สถานะอื่นไม่มี และยิง URL เองก็ไม่ได้
3. รีวิวใหม่ขึ้นในหน้าสินค้าพร้อมป้ายวิธีชง + "ซื้อแล้ว · ขนาด · การบด"; ค่าเฉลี่ยรวมและตามวิธีชงอัปเดต
4. กรองรีวิวตามวิธีชงได้ และการ์ดสินค้ามี ★ เฉลี่ย, เรียงตามคะแนนได้
5. ข้อมูลตัวอย่างมีรีวิวพอให้หน้าสินค้ายอดนิยมเห็นค่าเฉลี่ยอย่างน้อย 2 วิธีชง
6. `sail artisan test` ผ่าน, `sail pint --test` สะอาด

## Decisions (2026-10-23)

1. ความเห็นไม่บังคับ (ให้ดาว + วิธีชงอย่างเดียวได้)
2. แก้/ลบรีวิวของตัวเองได้ตลอด
3. ชื่อผู้รีวิวแสดงแบบ "สมชาย ใ."
4. `reviews` เก็บแค่ `order_item_id` — ผู้รีวิวและสินค้าหาด้วย JOIN (ตาม 3NF)
