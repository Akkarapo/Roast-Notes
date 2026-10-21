# Todo: ordering

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [ ] **O1 — Schema, enums, models, factories**
  - Acceptance: migrations carts, cart_items, orders, order_items, order_discounts ตาม spec; enums `Grind` (label ไทย), `OrderStatus` (6 สถานะ, label ไทย, `next()`, `canTransitionTo()`, `allowsReview()`, `isCancellableByMember()`); models + relations (`User::cart()`, `User::orders()`); factories
  - Verify: `tests/Feature/Ordering/SchemaTest.php`, `tests/Unit/Ordering/OrderStatusTest.php`
  - Files: migration, `app/Enums/Grind.php`, `app/Enums/OrderStatus.php`, `app/Models/{Cart,CartItem,Order,OrderItem,OrderDiscount}.php`, factories

- [ ] **O2 — Pricing pipeline**
  - Acceptance: `DiscountRule`, `PricingContext`, `DiscountLine`, `Quote`, `PriceCalculator` (ค่าส่ง ฿50 / ฟรี ≥ ฿1,000 จาก config, ส่วนลดรวมไม่เกิน subtotal, กฎเรียงตาม config)
  - Verify: `tests/Unit/Ordering/PriceCalculatorTest.php` (กฎปลอม 2 ตัว)
  - Files: `app/Support/Pricing/*.php`, `config/roastnotes.php`, `composer.json` (ext-bcmath)

- [ ] **O3 — ใส่ตะกร้า**
  - Acceptance: `AddToCart` action (รวมบรรทัดซ้ำ, ดริปแบ็ก grind `none`, จำนวน 1–20, ≤ สต็อก, inactive ปฏิเสธ); ฟอร์มในหน้าสินค้า (ขนาด/การบด/จำนวน); guest ไป login แล้วกลับหน้าเดิม; flash "ใส่ตะกร้าแล้ว"; badge ใน navbar
  - Verify: `tests/Feature/Ordering/AddToCartTest.php`
  - Files: `app/Actions/AddToCart.php`, `CartItemController@store`, `AddToCartRequest`, `products/show.blade.php`, navigation + view composer

- [ ] **O4 — หน้าตะกร้า**
  - Acceptance: รายการ + แก้การบด/จำนวน/ลบ (Policy), กล่องสรุปจาก `Quote`, แจ้งเกินสต็อก + ปิดปุ่มชำระเงิน, ข้อความ "อีก ฿x ส่งฟรี"
  - Verify: `tests/Feature/Ordering/CartTest.php`
  - Files: `CartController`, `CartItemController@update/destroy`, `CartItemPolicy`, `cart/index.blade.php`, `components/order-summary.blade.php`

- [ ] **O5 — Checkout**
  - Acceptance: GET เติมที่อยู่จากโปรไฟล์; POST ผ่าน `PlaceOrder` (transaction, lock, คำนวณใหม่, snapshot, discounts, ตัดสต็อก, ล้างตะกร้า, เลขออเดอร์); สต็อกไม่พอ → กลับตะกร้าพร้อมรายละเอียด ไม่สร้างอะไร
  - Verify: `tests/Feature/Ordering/CheckoutTest.php`
  - Files: `app/Actions/PlaceOrder.php`, `app/Exceptions/OutOfStock.php`, `CheckoutController`, `CheckoutRequest`, `checkout/index.blade.php`

- [ ] **O6 — หน้าออเดอร์ + ยกเลิก + ประวัติ**
  - Acceptance: `/orders/{order_number}` ใบสรุป; คนอื่น 404; ยกเลิก pending คืนสต็อก; การ์ดประวัติในหน้าสมาชิก (10 ล่าสุด)
  - Verify: `tests/Feature/Ordering/OrderTest.php`
  - Files: `OrderController`, `OrderPolicy`, `app/Actions/CancelOrder.php`, `orders/show.blade.php`, `profile/edit.blade.php`, `components/order-status-badge.blade.php`

- [ ] **O7 — ออเดอร์ตัวอย่าง + ขายดี**
  - Acceptance: `OrderSeeder` ~60 ออเดอร์ 90 วันของ member1–3, deterministic, ไม่แตะสต็อก; หน้าแรกส่วน "ขายดี" 4 อันดับ (ไม่นับ cancelled, ซ่อนถ้าไม่มี)
  - Verify: `tests/Feature/Ordering/OrderSeederTest.php`, `tests/Feature/Ordering/BestSellersTest.php`
  - Files: `database/seeders/OrderSeeder.php`, `DatabaseSeeder.php`, `HomeController`, `home.blade.php`

- [ ] **O8 — Checkpoint**
  - Acceptance: Success Criteria 1–7, screenshot desktop/มือถือ, pint สะอาด, อัปเดต CAPABILITY-MAP
  - Verify: ไล่ทีละข้อ + `sail artisan test`
