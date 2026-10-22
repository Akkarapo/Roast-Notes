# Todo: sales-report

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [x] **R1 — โครง**: `DateRange`, `Report` interface, `Admin\ReportController` (index, csv), ตัวเลือกช่วงเวลา, แท็บ "รายงาน" · Verify: `tests/Feature/Reports/DateRangeTest.php`, access ใน ReportPageTest
- [x] **R2 — กราฟ SVG**: `x-chart.bars`, `x-chart.hbars` + ตารางคู่ · Verify: ตรวจในเบราว์เซอร์ + assert ใน ReportPageTest
- [x] **R3 — สรุป + ยอดตามเวลา** · Verify: `SummaryTest.php`, `SalesOverTimeTest.php`
- [x] **R4 — ขายดี, ระดับคั่ว, โน้ต** · Verify: `TopProductsTest.php`, `RoastShareTest.php`, `FlavorNotesTest.php`
- [x] **R5 — ส่วนลดตามโปร, รสที่ชอบ vs ขายได้** · Verify: `DiscountsTest.php`, `TasteVsSalesTest.php`
- [x] **R6 — ดู SQL + CSV** · Verify: `CsvTest.php`, `ReportPageTest.php`
- [ ] **R7 — Checkpoint**: Success Criteria 1–6, screenshot, pint, CAPABILITY-MAP
