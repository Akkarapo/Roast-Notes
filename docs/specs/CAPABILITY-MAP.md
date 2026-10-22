# Capability Map: Roast & Notes (Specialty Coffee Beans Shop)

> Phase 0 ของ Spec-Driven Development — แผนที่โมดูลก่อนเขียน spec รายโมดูล
> แหล่งที่มา: โจทย์ Term Project (261343) + ไอเดียทีม (attachment.txt)
> สถานะ: **อนุมัติแล้ว (2026-10-21)**
> ผู้พัฒนา: 1 คน (ทำทุกโมดูลเอง) · Repo: https://github.com/Akkarapo/Roast-Notes

## โมดูล

| Module id | ความรับผิดชอบ | ตารางที่เป็นเจ้าของ | Depends on |
|---|---|---|---|
| foundation | โปรเจค Laravel + Sail + MySQL, layout หลัก, Register/Login, role member/admin, middleware admin | users | — |
| catalog | สินค้า + variant (ขนาดถุง/ราคา/สต็อก), โน้ตรสชาติ, หน้า Products (กรองโน้ต/ระดับคั่ว/แหล่งปลูก/ประเภท), หน้ารายละเอียดสินค้า, Home ส่วน "ล็อตใหม่" | origins, products, cupping_scores (1:1), product_variants, flavor_notes, product_flavor_notes | foundation |
| taste-profile | Taste Quiz 3 ข้อหลังสมัคร, แก้ Taste Profile, คำนวณ "เข้ากับลิ้นคุณ X%", Home ส่วน "แนะนำสำหรับคุณ" | user_taste_preferences (+ ระดับคั่ว/วิธีชงที่ชอบใน users) | foundation, catalog |
| ordering | Cart (เลือกขนาด/การบด), Checkout, ยืนยันออเดอร์, ตัดสต็อก, Member Info + ประวัติสั่งซื้อ, Home ส่วน "ขายดี"; เป็นเจ้าของ **pricing pipeline** ที่โมดูลส่วนลดมาเสียบ | carts, cart_items, orders, order_items, order_discounts | catalog |
| promotions | Coupon % (เฉพาะสินค้าร่วมรายการ), Buy X Get Y, สรุปส่วนลดใน checkout | promotions (supertype), coupons, buy_x_get_y, promotion_products | ordering |
| loyalty | ระดับ Bean / Barista / Roaster Master ตามยอดซื้อสะสม, ส่วนลดตามระดับ, สิทธิ์ซื้อล็อตหายากก่อน | member_tiers | ordering |
| community | Wish List, Reviews (เฉพาะคนที่ซื้อแล้วและออเดอร์ "ได้รับแล้ว", 1 รีวิวต่อ 1 รายการที่ซื้อ, ระบุวิธีชง, กรองตามวิธีชง), Rating 1–5 + ค่าเฉลี่ยแยกตามวิธีชง | wishlists, reviews | catalog, ordering |
| admin | Admin Dashboard: จัดการสินค้า/ขนาดถุง/สต็อก/โปรโมชั่น/ออเดอร์/ผู้ใช้ | — (ใช้ตารางของโมดูลอื่น) | catalog, ordering, promotions, loyalty |
| sales-report | ยอดขายรายเดือน, เมล็ดขายดี, สัดส่วนตามระดับคั่ว, โน้ตรสชาติยอดนิยม | — (query อย่างเดียว) | ordering, taste-profile |

## ลำดับการสร้าง

```
foundation → catalog → ordering → taste-profile → promotions → loyalty → community → admin → sales-report
```

- ทำคนเดียว จึงเรียงทีละโมดูล ให้ `ordering` มาก่อน `taste-profile` เพื่อให้ฟีเจอร์หลักของโจทย์ใช้งานได้ครบเร็วที่สุด
- Core features ของโจทย์ครบเมื่อ `foundation + catalog + ordering` เสร็จ
- Extra features (โจทย์ขั้นต่ำ 3 ข้อ): coupon, buy X get Y, loyalty, wish list, reviews, rating, admin, sales report = ครบ 8 ข้อ

## ขอบเขตระหว่างโมดูล (สัญญาที่ต้องนิยามใน spec ของโมดูลผู้ให้บริการ)

- `ordering` นิยาม interface ส่วนลด (เช่น `DiscountRule::apply(Cart): DiscountLine[]`) → `promotions` และ `loyalty` implement
- `catalog` เปิด query scope สำหรับกรองสินค้า → `taste-profile` ใช้ต่อเพื่อเรียงตาม match %
- cart_items / order_items อ้างอิง `variant_id` ไม่ใช่ `product_id`; order_items เก็บราคา snapshot ณ วันสั่ง
- `community` ใช้ `order_items` ของ `ordering` เป็นหลักฐานการซื้อ: `reviews.order_item_id` เป็น FK + UNIQUE

## การตัดสินใจ (2026-10-21)

| เรื่อง | ข้อสรุป | ผลต่อ schema |
|---|---|---|
| สิทธิ์รีวิว | รีวิวได้เฉพาะคนที่ซื้อเมล็ดนั้นแล้ว, รีวิวได้ 1 ครั้งต่อ 1 รายการที่ซื้อ (ซื้อซ้ำ = รีวิวเพิ่มได้) | `reviews.order_item_id` UNIQUE, NOT NULL |
| การบด | ไม่มีผลต่อราคาหรือสต็อก | `product_variants` = ขนาดถุงอย่างเดียว (product_id + size UNIQUE); `grind` อยู่ที่ `cart_items` และ `order_items` |
| การแบ่งงาน | ทำคนเดียวทุกโมดูล | build order แบบเรียงลำดับ |

## สถานะโมดูล

| Module id | Spec | Plan/Tasks | Implement |
|---|---|---|---|
| foundation | ✅ อนุมัติ | ✅ อนุมัติ | ✅ เสร็จ (51 tests ผ่าน) |
| catalog | ✅ อนุมัติ | ✅ อนุมัติ | ✅ เสร็จ (40 เมล็ด + 8 ดริปแบ็กจาก CQI) |
| ordering | ✅ อนุมัติ | ✅ อนุมัติ | ✅ เสร็จ (Core features ของโจทย์ครบ) |
| taste-profile | ✅ อนุมัติ | ✅ อนุมัติ | ✅ เสร็จ |
| promotions | ✅ อนุมัติ | ✅ อนุมัติ | ✅ เสร็จ (คูปอง % + ซื้อ X แถม Y) |
| loyalty | ✅ อนุมัติ | 📝 รอรีวิว | — |
| community … sales-report | — | — | — |

## สมมติฐาน (แก้ได้ก่อนเขียน spec)

1. Laravel 12 + PHP 8.3 + Laravel Sail + MySQL 8 (ต่อจากแลปที่ใช้ Sail)
2. หน้าเว็บเป็น Blade + Tailwind, ระบบ auth ใช้ Laravel Breeze (Blade stack) — ไม่ทำ SPA
3. ไม่มีการจ่ายเงินจริง: checkout = ยืนยันออเดอร์ สถานะ `pending` → admin เปลี่ยนสถานะ
4. Match % = (จำนวนโน้ตที่ตรงกัน ÷ จำนวนโน้ตของเมล็ด) × 100 ตามที่ทีมเขียนไว้ ระดับคั่วใช้แค่เรียงลำดับ "แนะนำสำหรับคุณ"
5. ราคาและสต็อกแยกต่อ variant (ขนาดถุง) — การบดเลือกตอนใส่ตะกร้า
6. ส่วนลดซ้อนกันได้: Buy X Get Y อัตโนมัติ → คูปอง 1 ใบต่อออเดอร์ → ส่วนลดระดับสมาชิกคิดจากยอดที่เหลือ
7. เกณฑ์ระดับสมาชิก: Bean ฿0 / Barista ≥ ฿25,000 (ลด 5%) / Roaster Master ≥ ฿50,000 (ลด 10% + ซื้อล็อตหายากก่อน) — ปรับใน SPEC-loyalty
8. UI ภาษาไทย ชื่อตาราง/โค้ดภาษาอังกฤษ
