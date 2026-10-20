# Todo: catalog

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [ ] **C1 — คัดข้อมูล CQI**
  - Acceptance: `database/data/select_cqi.py` อ่าน `arabica_data_cleaned.csv` → คัดล็อตครบข้อมูล & ≥ 82 → แบ่งชั้นตาม Acidity:Body (Light 13 / Medium 14 / Dark 13, รวมไทยทุกล็อตที่ผ่าน) → `database/data/cqi-selected.csv` 40 แถว + คอลัมน์ `drip_bag` = 1 สำหรับ 8 ล็อตคะแนนสูงสุด; ทำความสะอาดชื่อภูมิภาค/ประเทศ; `database/data/README.md` มีที่มา + MIT notice
  - Verify: รันสคริปต์ซ้ำได้ CSV เดิม (diff ว่าง), เปิด CSV ตรวจด้วยตา
  - Files: `database/data/select_cqi.py`, `database/data/cqi-selected.csv`, `database/data/README.md`

- [ ] **C2 — Migrations + enums**
  - Acceptance: 7 ตารางตาม spec (origins, flavor_categories, flavor_notes, products, product_flavor_notes, product_variants, cupping_scores) พร้อม FK, unique, index, cascade; enums `ProductType`, `ProcessMethod`, `RoastLevel`, `BagSize` มี `label()`
  - Verify: `tests/Feature/Catalog/SchemaTest.php` (unique, cascade)
  - Files: `database/migrations/*_create_catalog_tables.php`, `app/Enums/*.php`, test

- [ ] **C3 — Models + relations + factories**
  - Acceptance: `Origin`, `FlavorCategory` (parent/children), `FlavorNote`, `Product` (origin, variants, flavorNotes, cupping, scopes `active`, `filter`), `ProductVariant`, `CuppingScore`; factories พร้อม states `dripBag()`, `inactive()`, `withVariants()`
  - Verify: SchemaTest + เทส relation พื้นฐาน
  - Files: `app/Models/*.php`, `database/factories/*.php`

- [ ] **C4 — DemoDataRules**
  - Acceptance: คลาสเดียวคำนวณ โน้ต (ตามตารางน้ำหนัก + จำนวนตาม tier), ระดับคั่ว (ratio), ราคา (hedonic), อายุคั่ว (normal 15±6, clamp 7–60); deterministic ต่อ `cqi_ref`
  - Verify: `tests/Unit/Catalog/DemoDataRulesTest.php`
  - Files: `app/Support/Catalog/DemoDataRules.php`, test

- [ ] **C5 — Seeders**
  - Acceptance: `FlavorTaxonomySeeder` (5 หมวดหลัก, หมวดย่อย, ~35 โน้ต) + `CatalogSeeder` อ่าน CSV → origins, products (+ ดริปแบ็ก 8), variants, cupping, โน้ต; `DatabaseSeeder` เรียกต่อจาก `UserSeeder`
  - Verify: `tests/Feature/Catalog/CatalogSeederTest.php` + ตรวจข้อมูลจริงหลัง `migrate:fresh --seed`
  - Files: `database/seeders/FlavorTaxonomySeeder.php`, `database/seeders/CatalogSeeder.php`, `DatabaseSeeder.php`, test

- [ ] **C6 — หน้า /products**
  - Acceptance: ตัวกรอง 4 แบบ + เรียง 4 แบบ ผ่าน `ProductFilterRequest`, paginate 12 + คง query, chips ตาม DESIGN.md, `<x-product-card>` (เว้น slot match badge, ป้ายหมด), empty state + ล้างตัวกรอง, มือถือเลื่อนตัวกรองแนวนอน
  - Verify: `tests/Feature/Catalog/ProductListTest.php` (รวม query count)
  - Files: `app/Http/Controllers/ProductController.php`, `app/Http/Requests/ProductFilterRequest.php`, `resources/views/products/index.blade.php`, `resources/views/components/product-card.blade.php`, `routes/web.php`

- [ ] **C7 — หน้า /products/{slug}**
  - Acceptance: ข้อมูลล็อต, เลือกขนาด (radio chips, หมด = disabled), ปุ่มใส่ตะกร้าแบบชั่วคราว, แถบ cupping 6 ด้าน + คะแนนรวม, เมล็ดที่คล้ายกัน 4 ตัว; 404 เมื่อไม่พบ/inactive
  - Verify: `tests/Feature/Catalog/ProductShowTest.php`
  - Files: `ProductController@show`, `resources/views/products/show.blade.php`, `resources/views/components/cupping-bars.blade.php`

- [ ] **C8 — หน้าแรก "เมล็ดล็อตใหม่"**
  - Acceptance: 4 การ์ด roast_date ล่าสุด (active) + "ดูทั้งหมด →"
  - Verify: `tests/Feature/Catalog/HomeNewLotsTest.php`
  - Files: `app/Http/Controllers/HomeController.php`, `routes/web.php`, `resources/views/home.blade.php`

- [ ] **C9 — Checkpoint**
  - Acceptance: Success Criteria 1–7 ครบ, screenshot desktop/มือถือ, `pint --test` สะอาด, อัปเดต CAPABILITY-MAP + ภาพใน `docs/design/`
  - Verify: ไล่ทีละข้อ + `sail artisan test`
