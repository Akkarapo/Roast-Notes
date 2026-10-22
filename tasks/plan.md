# Plan: sales-report

> อ้างอิง [docs/specs/SPEC-sales-report.md](../docs/specs/SPEC-sales-report.md) · สถานะ: **รอรีวิว**

## ลำดับ

```
R1 DateRange + Report interface + ReportController (index/csv) + หน้าเปล่าพร้อมตัวเลือกช่วงเวลา + เมนู/ลิงก์
 ├─ R2 กราฟ SVG: x-chart.bars, x-chart.hbars (+ ตารางคู่)
 ├─ R3 สรุป + ยอดขายตามช่วงเวลา
 ├─ R4 สินค้าขายดี + สัดส่วนระดับคั่ว + โน้ตรสชาติ
 └─ R5 ส่วนลดตามโปร + รสที่ชอบ vs ขายได้
     └─ R6 "ดู SQL" + CSV ทุกรายงาน
         └─ R7 Checkpoint
```

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| เขียน query | Query Builder (`DB::table`) ไม่ใช่ Eloquent | SQL ที่โชว์อ่านง่าย ตรงกับที่สอนในวิชา |
| ช่องว่างในกราฟเวลา | เติม 0 ใน PHP หลัง GROUP BY | ไม่ต้องมีตารางปฏิทิน |
| เงิน | `SUM` ใน SQL แล้วแปลงเป็นสตางค์ด้วย `Money::toSatang` | ไม่ใช้ float คำนวณต่อ |
| % เปลี่ยน | คำนวณใน PHP จากสองช่วง; ช่วงก่อนเป็น 0 → "ใหม่" | ไม่หารศูนย์ |
| SVG | คำนวณพิกัดใน Blade component จากชุดข้อมูล {label, value} | ไม่มี JS, เทสได้ด้วย assertSee |
| CSV | `response()->streamDownload` + BOM `\xEF\xBB\xBF` | Excel อ่านไทยได้ |
| เมนู | แท็บ "รายงาน" ในเมนูหลังร้าน + ปุ่มบน Dashboard | หาเจอง่าย |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| รายงานรสนับซ้ำเมื่อเมล็ดมีหลายโน้ตในหมวดเดียว | ใช้ `COUNT(DISTINCT order_items.id)`/`EXISTS` ต่อหมวด และเทสกรณีนี้ |
| `ONLY_FULL_GROUP_BY` ของ MySQL | groupBy ทุกคอลัมน์ที่ select |
| ข้อมูลตัวอย่างอยู่ใน 90 วัน ช่วง 12 เดือนดูโล่ง | ค่าเริ่มต้น 90 วัน |

## Checkpoints
- หลัง R3: ยอด "เดือนนี้" ตรงกับ Dashboard
- R7: Success Criteria 1–6 + screenshot
