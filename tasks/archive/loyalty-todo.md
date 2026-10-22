# Todo: loyalty

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [x] **L1 — Schema + models**: `member_tiers` (+ Bean/Barista/Roaster Master), `users.member_tier_id`, `products.exclusive_until`; `MemberTier`, relations · Verify: `tests/Feature/Loyalty/SchemaTest.php`
- [x] **L2 — ยอดสะสม + ขึ้นระดับ**: `RecalculateMemberTier`, `Order::markDelivered()`, `LoyaltyStatus` · Verify: `RecalculateMemberTierTest.php`
- [x] **L3 — ส่วนลดระดับ**: `LoyaltyTierRule` ใน config ต่อจาก CouponRule · Verify: `LoyaltyTierRuleTest.php`, `CheckoutLoyaltyTest.php`
- [x] **L4 — หน้าจอระดับ**: การ์ดใน Member Info (progress bar), ป้ายใน navbar, ข้อความชวนในตะกร้า · Verify: `MemberInfoTierTest.php`
- [x] **L5 — ล็อตหายาก**: policy `purchase`, ปฏิเสธใน AddToCart + PlaceOrder, ป้าย "Roaster Master ซื้อก่อน" + ปุ่มปิด · Verify: `EarlyAccessTest.php`
- [x] **L6 — LoyaltySeeder**: คำนวณระดับ demo, ล็อตหายาก 3 ตัว · Verify: seed แล้วได้ member1/2/3 = RM/Barista/Bean
- [x] **L7 — Checkpoint**: Success Criteria 1–5, screenshot, pint, CAPABILITY-MAP
