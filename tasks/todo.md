# Todo: promotions

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [x] **P1 — Schema + models**: promotions, coupons, buy_x_get_y, promotion_products, `order_discounts.promotion_id`; `Promotion` (scope `currentlyActive`), `Coupon`, `BuyXGetY`; factories · Verify: `tests/Feature/Promotions/SchemaTest.php`
- [x] **P2 — ขยาย pipeline**: `PricingContext::appliedDiscounts`, meta `promotion_id` + `lines`; `PlaceOrder` บันทึก promotion_id · Verify: เทส ordering เดิมผ่าน + เทสใหม่
- [x] **P3 — BuyXGetYRule**: ทุก X+Y → Y ตัวถูกสุดฟรี, ซ้ำได้, เฉพาะสินค้าร่วมรายการ, ข้อความชวนเพิ่ม · Verify: `BuyXGetYRuleTest.php`
- [x] **P4 — Coupon**: `CouponValidator` (6 เหตุผล), `CouponRule` (ฐานหักของแถม, ปัดลง) · Verify: `CouponValidatorTest.php`, `CouponRuleTest.php`
- [x] **P5 — คูปองในตะกร้า + Checkout**: POST/DELETE `/cart/coupon`, ชิปคูปอง, error, Checkout แสดงอย่างเดียว, PlaceOrder ตรวจซ้ำ + lock, ล้าง session หลังสั่ง · Verify: `CartCouponTest.php`, `CheckoutPromotionTest.php`
- [x] **P6 — ป้ายโปร + ตัวกรอง + seeder**: ริบบิ้นบนการ์ด/หน้าสินค้า, ตัวกรอง "มีโปรโมชั่น", `PromotionSeeder` 4 โปร · Verify: `PromoBadgeTest.php`
- [x] **P7 — Checkpoint**: Success Criteria 1–7, screenshot, pint, CAPABILITY-MAP
