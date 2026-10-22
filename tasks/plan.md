# Plan: admin

> อ้างอิง [docs/specs/SPEC-admin.md](../docs/specs/SPEC-admin.md) · สถานะ: **รอรีวิว**

## ลำดับ

```
A1 Admin layout + เมนูย่อย + components (stat-card, table, status-tabs) + AccessTest โครง
A2 order_status_changes + Order::recordStatus + บันทึกใน PlaceOrder/CancelOrder + timeline ฝั่งสมาชิก + seed ประวัติ
 └─ A3 ออเดอร์แอดมิน: รายการ/แท็บ/ค้นหา, รายละเอียด, AdvanceOrderStatus, ยกเลิกพร้อมเหตุผล
A4 สินค้าแอดมิน: รายการ, ฟอร์มสร้าง/แก้ + ขนาดถุง + โน้ต, ปิดขาย/ลบ
A5 โปรโมชั่นแอดมิน: รายการ + สถานะ, สร้าง/แก้ตามชนิด, เลือกสินค้า, เปิด/ปิด/ลบ
A6 สมาชิกแอดมิน: รายการ/ค้นหา, รายละเอียด, ปรับระดับ, ตั้ง/ถอดแอดมิน
A7 รีวิวแอดมิน: รายการ/กรอง, ลบ
A8 ภาพรวม (Dashboard): การ์ดตัวเลข, ออเดอร์ล่าสุด, ใกล้หมด
 └─ A9 Checkpoint
```

ทำ Dashboard ท้ายสุดเพราะลิงก์ไปทุกหน้าย่อย

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| ป้องกันสิทธิ์ | middleware `admin` ที่มีอยู่ครอบทั้งกลุ่ม route | จุดเดียว, AccessTest วนทุก route ชื่อ `admin.*` |
| เปลี่ยนสถานะ | action `AdvanceOrderStatus` + `CancelOrder($order, byMember: false)` เดิม | กติกาอยู่ที่ enum เดิม |
| ประวัติสถานะ | `Order::recordStatus()` เรียกใน transaction เดียวกับการเปลี่ยน | ไม่มีทางเปลี่ยนแล้วลืมบันทึก |
| ขนาดถุงในฟอร์มสินค้า | array `variants[size][price|stock|...]` ใน FormRequest เดียว, `SaveProduct` action sync ใน transaction | บันทึกทีเดียวทั้งสินค้า |
| ลบได้ไหม | `Product::hasBeenOrdered()` (exists บน order_items ผ่าน variants) | ตรงกับ FK restrict |
| ฟอร์มโปร | ชนิดมาจาก query `?type=` ตอนสร้าง, ตอนแก้ล็อกชนิด | disjoint subtype |
| ค้นหา | `LIKE %q%` บนคอลัมน์ที่กำหนด | ข้อมูลเล็ก ไม่ต้อง full-text |
| ปรับระดับเอง | update `member_tier_id` ตรง + flash · ไม่เพิ่มตาราง log | ตามขอบเขตที่ตกลง |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| ฟอร์มสินค้ายาวและ validation ซับซ้อน (ขนาดถุง) | แยก FormRequest + เทสกรณีขอบ: ขนาดซ้ำ, ราคา 0, ลบขนาดที่เคยขาย |
| AdvanceOrderStatus กับ markDelivered ซ้อน transaction | markDelivered ใช้ DB::transaction (nested savepoint ได้) — เทสยืนยัน |
| งานเยอะสุดในโปรเจค | ทำทีละหน้า commit ทีละ task, UI ใช้ component ร่วม |

## Checkpoints
- หลัง A3: เดินออเดอร์จริงในเบราว์เซอร์ pending → delivered แล้วดู timeline ฝั่งสมาชิก
- A9: Success Criteria 1–7 + screenshot
