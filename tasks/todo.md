# Todo: community

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [x] **C1 — Schema + models**: `wishlists`, `reviews` (order_item_id UNIQUE, CHECK rating), `Review`, relations, factories · Verify: `tests/Feature/Community/SchemaTest.php`
- [ ] **C2 — Wish List**: POST/DELETE `/wishlist/{product}`, หน้า `/wishlist`, `x-wishlist-button` บนการ์ด/หน้าสินค้า, "Wish List (n)" ในเมนู · Verify: `WishlistTest.php`
- [ ] **C3 — รีวิว**: `ReviewPolicy`, `SubmitReview`, ฟอร์มสร้าง/แก้/ลบ (`x-star-input`), ปุ่มในหน้าออเดอร์ delivered · Verify: `ReviewEligibilityTest.php`, `ReviewCrudTest.php`
- [ ] **C4 — Rating**: `scopeWithRating`, ★ บนการ์ด, section รีวิวในหน้าสินค้า (สรุป, ตามวิธีชง, กรอง `?brew=`, หน้าละ 10), เรียง "คะแนนรีวิว" · Verify: `RatingTest.php`
- [ ] **C5 — CommunitySeeder**: รีวิว ~60% ของรายการ delivered, Wish List สมาชิกละ 3–5 · Verify: `CommunitySeederTest.php`
- [ ] **C6 — Checkpoint**: Success Criteria 1–6, screenshot, pint, CAPABILITY-MAP
