# Spec: sales-report

> โมดูลที่ 9 (สุดท้าย) ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **อนุมัติแล้ว (2026-10-23)** · Depends on: ordering, catalog, taste-profile, promotions, loyalty · ไม่มีตารางใหม่ (query อย่างเดียว)

## Objective

Extra Feature **Sales Report** ของโจทย์: หน้าเดียวในหลังร้านที่ตอบว่า "ขายได้เท่าไร ขายอะไร ใครชอบอะไร" สำหรับช่วงเวลาที่เลือก และเป็นที่โชว์ **SQL แบบ GROUP BY / JOIN / aggregate** ตอนพรีเซนต์วิชา Database

**User stories**
- ในฐานะแอดมิน ฉันเลือกช่วงเวลา (ปุ่มลัดหรือกำหนดเอง) แล้วเห็นยอดขาย ออเดอร์ ค่าเฉลี่ยต่อออเดอร์ เทียบกับช่วงก่อนหน้า
- ในฐานะแอดมิน ฉันเห็นยอดขายรายเดือน (หรือรายวันถ้าช่วงสั้น), เมล็ดขายดี, สัดส่วนตามระดับคั่ว, โน้ตรสชาติยอดนิยม, ส่วนลดที่ให้ไปตามโปร, และรสที่ลูกค้าบอกว่าชอบเทียบกับรสที่ขายได้จริง
- ในฐานะแอดมิน ฉันกด "ดู SQL" ใต้รายงานแต่ละอันเพื่อเห็น query ที่ใช้จริง และดาวน์โหลด CSV ได้

**นอกขอบเขต**: กราฟแบบ interactive (zoom/tooltip ด้วย JS), ส่งรายงานทางอีเมล, ตั้งเวลารายงาน, เปรียบเทียบหลายช่วงพร้อมกัน, เก็บ snapshot รายงาน

## นิยาม

- **ยอดขาย** = ผลรวม `orders.total` (หลังส่วนลด รวมค่าส่ง) ของออเดอร์ที่ **ไม่ถูกยกเลิก** ตามวันที่สั่ง (`placed_at`) — ตรงกับการ์ดบน Dashboard
- **ชิ้นที่ขาย / ยอดขายต่อสินค้า** = จาก `order_items` (quantity, line_total ก่อนส่วนลดระดับออเดอร์) ของออเดอร์ที่ไม่ถูกยกเลิก
- **ช่วงก่อนหน้า** = ช่วงยาวเท่ากันที่จบก่อนวันเริ่ม (เช่น 90 วันล่าสุด เทียบกับ 90 วันก่อนหน้านั้น)
- เวลาทั้งหมดตาม `APP_TIMEZONE` (Asia/Bangkok) · เงินแสดงเป็นบาท คำนวณเป็นสตางค์

## ช่วงเวลา

- ปุ่มลัด: **30 วัน · 90 วัน (ค่าเริ่มต้น) · 12 เดือน · ปีนี้**
- กำหนดเอง: วันที่เริ่ม – วันที่สิ้นสุด (รวมทั้งสองวัน) · สิ้นสุดต้องไม่ก่อนเริ่ม · ยาวสุด 3 ปี
- query string: `?range=90d` หรือ `?from=2026-08-01&to=2026-10-23` → ทุกรายงานและ CSV ใช้ช่วงเดียวกัน

## รายงาน (หน้า `/admin/reports`)

| # | รายงาน | แสดงเป็น | SQL หลัก |
|---|---|---|---|
| 1 | **สรุป**: ยอดขาย, ออเดอร์, ค่าเฉลี่ยต่อออเดอร์, ชิ้นที่ขาย, ส่วนลดรวม — พร้อม % เปลี่ยนจากช่วงก่อนหน้า | การ์ดตัวเลข 5 ใบ | `SUM/COUNT/AVG` บน orders, `SUM(quantity)` บน order_items |
| 2 | **ยอดขายตามช่วงเวลา**: รายวันถ้าช่วง ≤ 62 วัน ไม่งั้นรายเดือน (มีช่องว่างเป็น 0) | กราฟแท่ง SVG + ตาราง | `GROUP BY DATE(placed_at)` / `DATE_FORMAT(placed_at, '%Y-%m')` |
| 3 | **สินค้าขายดี 10 อันดับ** (ตามชิ้น, แสดงยอดเงินด้วย) | ตารางพร้อมแท่งในแถว | `JOIN order_items → product_variants → products GROUP BY products.id ORDER BY SUM(quantity) DESC LIMIT 10` |
| 4 | **สัดส่วนตามระดับคั่ว** (ชิ้น + ยอดเงิน + %) | แท่งแนวนอน 3 แท่ง | `GROUP BY products.roast_level` |
| 5 | **โน้ตรสชาติยอดนิยม 10 อันดับ** (ชิ้นของเมล็ดที่มีโน้ตนั้น) + รวมตามหมวดหลัก | แท่งแนวนอน | `JOIN product_flavor_notes → flavor_notes GROUP BY flavor_notes.id` |
| 6 | **ส่วนลดตามโปร**: ทุกแหล่ง (คูปองแต่ละใบ, ซื้อ X แถม Y, ระดับสมาชิก) — ใช้กี่ครั้ง, รวมกี่บาท | ตาราง | `order_discounts LEFT JOIN promotions GROUP BY source, promotion_id` |
| 7 | **รสที่ชอบ vs รสที่ขายได้**: ต่อหมวดรสหลัก 5 หมวด — % สมาชิกที่เลือกใน Taste Quiz เทียบ % ของชิ้นที่ขายที่มีโน้ตในหมวดนั้น | แท่งคู่ (2 สี) + ตาราง | `user_taste_preferences GROUP BY flavor_category_id` + `order_items JOIN … flavor_categories (parent) GROUP BY` |

ทุกรายงาน:
- ไม่มีข้อมูลในช่วง → empty state ของรายงานนั้น (ไม่ error)
- **"ดู SQL"** (`<details>`) ใต้รายงาน: SQL ที่รันจริง (Query Builder → `toRawSql()`) จัดบรรทัดอ่านง่าย
- **"CSV"**: `GET /admin/reports/{key}.csv?range=…` ดาวน์โหลดข้อมูลของรายงานนั้น (UTF-8 + BOM ให้ Excel อ่านภาษาไทยได้)
- ลิงก์ "รายงานยอดขาย" บน Dashboard (เตรียมไว้แล้ว) จะโผล่เอง

## กราฟ (SVG ฝั่ง server)

- component `x-chart.bars` (แนวตั้ง, ช่วงเวลา) และ `x-chart.hbars` (แนวนอน, อันดับ/สัดส่วน) วาดด้วย Blade + SVG ล้วน ไม่มี JS
- โทนเดียวกับเว็บ: แท่ง cobalt บนพื้น paper, เส้นแกนบาง, ตัวเลขกำกับบนแท่งที่สำคัญ, ป้ายแกนภาษาไทย
- แท่งคู่ในรายงาน 7 ใช้ cobalt (ชอบ) กับ cobalt-ink ลายเส้น/โปร่ง (ขายได้) + legend
- ทุกกราฟมีตารางตัวเลขคู่กัน (accessibility + ใช้อ่านค่าจริง) และ `role="img"` + `aria-label` สรุป

## Code Style

```php
// app/Support/Reports/Report.php — แต่ละรายงานเป็นคลาสเล็ก ๆ
interface Report
{
    public function key(): string;                 // 'top-products'
    public function query(DateRange $range): Builder; // ใช้ทั้งแสดงผล, ดู SQL และ CSV
    /** @return list<string> */
    public function csvHeaders(): array;
    public function csvRow(object $row): array;
}

// app/Support/Reports/TopProducts.php
public function query(DateRange $range): Builder
{
    return DB::table('order_items')
        ->join('orders', 'orders.id', '=', 'order_items.order_id')
        ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
        ->join('products', 'products.id', '=', 'product_variants.product_id')
        ->where('orders.status', '!=', 'cancelled')
        ->whereBetween('orders.placed_at', $range->bounds())
        ->groupBy('products.id', 'products.name', 'products.type')
        ->selectRaw('products.id, products.name, products.type, SUM(order_items.quantity) AS units, SUM(order_items.line_total) AS revenue')
        ->orderByDesc('units')->limit(10);
}
```

- `App\Support\Reports\DateRange` (preset/from/to → bounds, previous(), granularity day|month, label)
- Controller `Admin\ReportController@index` และ `@csv`; รายงานลงทะเบียนใน `config('roastnotes.reports')`

## Testing Strategy

`tests/Feature/Reports/` — สร้างออเดอร์ที่รู้ตัวเลขแน่นอน แล้วเทียบผลแต่ละรายงานกับการคิดมือ

| เทส | ตรวจอะไร |
|---|---|
| DateRangeTest | ปุ่มลัด 4 แบบ, กำหนดเอง, สิ้นสุดก่อนเริ่ม/เกิน 3 ปี → ค่าเริ่มต้น, ช่วงก่อนหน้า, day/month |
| SummaryTest | ไม่นับยกเลิก, นอกช่วงไม่นับ, AOV, % เปลี่ยนจากช่วงก่อน (ก่อนเป็น 0 → แสดง "ใหม่") |
| SalesOverTimeTest | เติมวัน/เดือนที่ไม่มีขายเป็น 0, รวมตรงกับสรุป |
| TopProductsTest / RoastShareTest / FlavorNotesTest | อันดับ + ตัวเลขตรงกับการคิดมือ |
| DiscountsTest | แยกคูปองแต่ละใบ, แถม, ระดับสมาชิก; ออเดอร์ยกเลิกไม่นับ |
| TasteVsSalesTest | % สมาชิกที่ชอบแต่ละหมวด vs % ชิ้นที่ขาย |
| ReportPageTest | แอดมินเท่านั้น, ทุกรายงานมี SQL + ลิงก์ CSV, ช่วงว่าง = empty state, query count คงที่ |
| CsvTest | header + BOM + แถวตรงกับรายงาน, key ไม่รู้จัก → 404 |

## Boundaries

- **Always:** aggregate ใน SQL (ไม่ดึงทุกแถวมานับใน PHP) · ไม่นับออเดอร์ยกเลิก · ช่วงเวลาเดียวกันทุกรายงาน · SQL ที่โชว์คือที่รันจริง
- **Ask first:** เพิ่มตาราง summary/cache · กราฟ JS · นิยามยอดขายแบบอื่น
- **Never:** ให้ query รับ SQL/ชื่อคอลัมน์จาก URL · เปิดรายงานให้ member

## Success Criteria

1. `/admin/reports` แสดงทั้ง 7 รายงานด้วยข้อมูลตัวอย่าง (90 วัน) ภายใน query จำนวนคงที่
2. ตัวเลขสรุปของช่วง "เดือนนี้" ตรงกับการ์ดบน Dashboard
3. เปลี่ยนช่วงเวลาแล้วทุกรายงานและ CSV เปลี่ยนตาม
4. "ดู SQL" แสดง query จริงของทุกรายงาน, CSV เปิดใน Excel แล้วภาษาไทยไม่เพี้ยน
5. member เข้าไม่ได้ (403)
6. `sail artisan test` ผ่าน, `sail pint --test` สะอาด

## Decisions (2026-10-23)

1. ยอดขาย = ออเดอร์ที่ไม่ถูกยกเลิก ตามวันที่สั่ง
2. กราฟวาดด้วย SVG ฝั่ง server
3. ช่วงเวลา: ปุ่มลัด + กำหนดเอง
4. เพิ่ม: ปุ่ม "ดู SQL", CSV, ส่วนลดตามโปร, รสที่ชอบ vs รสที่ขายได้
