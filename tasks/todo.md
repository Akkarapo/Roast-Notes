# Todo: admin

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [x] **A1 — Admin layout**: `<x-admin-layout>` + เมนูย่อย 6 หน้า, components ร่วม · Verify: `tests/Feature/Admin/AccessTest.php` (guest → login, member → 403 ทุก route `admin.*`)
- [x] **A2 — ประวัติสถานะ**: ตาราง `order_status_changes`, `Order::recordStatus`, บันทึกใน PlaceOrder/CancelOrder, `x-order-timeline` ในหน้าออเดอร์สมาชิก, ประวัติใน OrderSeeder · Verify: `OrderStatusHistoryTest.php`
- [ ] **A3 — ออเดอร์**: รายการ (แท็บสถานะ + ค้นหา), รายละเอียด, `AdvanceOrderStatus`, ยกเลิก + เหตุผล · Verify: `OrderStatusTest.php`
- [ ] **A4 — สินค้า**: รายการ/กรอง, ฟอร์มสร้าง/แก้ + ขนาดถุง + โน้ต + ล็อตหายาก, ปิดขาย/ลบ · Verify: `ProductAdminTest.php`
- [ ] **A5 — โปรโมชั่น**: รายการ + สถานะ + จำนวนครั้งที่ใช้, สร้าง/แก้ตามชนิด, เลือกสินค้า, เปิด/ปิด/ลบ · Verify: `PromotionAdminTest.php`
- [ ] **A6 — สมาชิก**: รายการ/ค้นหา/กรอง, รายละเอียด, ปรับระดับ, ตั้ง/ถอดแอดมิน · Verify: `UserAdminTest.php`
- [ ] **A7 — รีวิว**: รายการ/กรอง/ค้นหา, ลบ · Verify: `ReviewAdminTest.php`
- [ ] **A8 — ภาพรวม**: การ์ดตัวเลข, ออเดอร์ล่าสุด, ใกล้หมด · Verify: `DashboardTest.php`
- [ ] **A9 — Checkpoint**: Success Criteria 1–7, screenshot, pint, CAPABILITY-MAP
