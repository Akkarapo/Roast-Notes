# Spec: catalog

> โมดูลที่ 2 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **รออนุมัติ** · Depends on: foundation · ผู้ใช้ต่อ: ordering, taste-profile, promotions, loyalty, community, admin, sales-report

## Objective

ทำให้ร้านมี "ของขาย" จริง: ข้อมูลเมล็ดกาแฟ + ขนาดถุง + โน้ตรสชาติ, หน้า **Products** ที่กรองได้, หน้ารายละเอียดสินค้า และส่วน "เมล็ดล็อตใหม่" บนหน้าแรก

ข้อมูลตั้งต้นมาจาก **Coffee Quality Institute (CQI) Arabica reviews** — ล็อตกาแฟจริงที่ถูกชิมให้คะแนนโดย Q-grader (ประเทศ, ภูมิภาค, ฟาร์ม, สายพันธุ์, วิธีแปรรูป, ความสูง, คะแนน cupping 10 ด้าน)

ครอบคลุม Core Feature ของโจทย์: **Products Page (public)** และเตรียมข้อมูลให้ Cart/Checkout

**User stories**
- ในฐานะผู้เยี่ยมชม ฉันเห็นเมล็ดทั้งหมดเป็นการ์ด และกรองตาม กลุ่มรสชาติ / ระดับคั่ว / ประเทศ / ประเภท (เมล็ด, ดริปแบ็ก) ได้พร้อมกัน
- ในฐานะผู้เยี่ยมชม ฉันเรียงสินค้าตาม คั่วล่าสุด / ราคา / คะแนน cupping ได้
- ในฐานะผู้เยี่ยมชม ฉันกดเข้าหน้าสินค้าแล้วเห็น แหล่งปลูก ฟาร์ม สายพันธุ์ ความสูง วิธีแปรรูป วันคั่ว โน้ตรส ขนาดถุงพร้อมราคา/สต็อก และคะแนน cupping จาก CQI
- ในฐานะผู้เยี่ยมชม หน้าแรกแสดงเมล็ดคั่วล่าสุด 4 ตัว

**นอกขอบเขต**: ปุ่มใส่ตะกร้าทำงานจริง + เลือกการบด (`ordering`), "เข้ากับลิ้นคุณ X%" (`taste-profile` — การ์ดเตรียมช่องไว้), รีวิว/คะแนนดาว (`community`), CRUD สินค้า (`admin`), ล็อตหายาก (`loyalty`)

## Data source: CQI

| เรื่อง | รายละเอียด |
|---|---|
| ต้นทาง | ชุดข้อมูลที่ผู้ใช้ให้มา (Kaggle: *Coffee Beans Reviews by Coffee Quality Institute*) เป็นชุดที่ scrape จาก CQI เดียวกับ [jldbc/coffee-quality-database](https://github.com/jldbc/coffee-quality-database) — ใช้ไฟล์ `arabica_data_cleaned.csv` จาก GitHub เพราะดาวน์โหลดจาก Kaggle ต้องล็อกอิน |
| License | MIT (© 2018 James LeDoux) — ต้องแนบ notice ไว้ใน `database/data/README.md` |
| ขนาด | 1,311 ล็อต, 37 ประเทศ (มีไทย 32 ล็อต) |
| คัดเลือก | ล็อตที่มีข้อมูลครบ (ภูมิภาค, สายพันธุ์, วิธีแปรรูป, ความสูง) และคะแนนรวม ≥ 82 → 596 ล็อต → คัด **40 ล็อต** กระจายหลายประเทศ (รวมไทยทุกล็อตที่ผ่านเกณฑ์) |
| ไฟล์ใน repo | `database/data/cqi-selected.csv` (40 แถว คัดแล้ว) + สคริปต์ที่ใช้คัด `database/data/select_cqi.py` เพื่อให้ทำซ้ำได้ |

**สิ่งที่ CQI ไม่มี** และต้องกำหนดเองแบบมีกฎ (ระบุใน seeder ว่าเป็นข้อมูลสาธิต):

| ฟิลด์ | กฎ (deterministic ต่อล็อต) |
|---|---|
| โน้ตรสชาติ | Natural → กลุ่ม fruity · Washed + acidity ≥ 8.0 → floral/fruity (citrus) · body ≥ 8.0 หรือบราซิล → chocolate/nutty · Honey/Pulped natural → caramel · ทุกล็อตได้ 2–4 โน้ต |
| ระดับคั่ว | acidity ≥ 8.0 และ washed → Light · body ≥ 8.0 หรือ acidity < 7.5 → Dark · อื่น ๆ → Medium |
| ราคา | 100g = 150 + (คะแนนรวม − 80) × 25 ปัดเป็นเลขลงท้าย 0 · 250g ≈ ×2.3 · 500g ≈ ×4.2 · ดริปแบ็ก 1 ซอง ≈ 100g ÷ 5 |
| วันคั่ว | สุ่มแบบกำหนด seed ภายใน 30 วันก่อนวันรัน seeder |
| ชื่อเล่น (ตัวลายมือบนการ์ด) | จากกลุ่มรสหลัก + ระดับคั่ว เช่น "Floral Light", "Nutty Comfort" |

ล็อตที่คะแนนสูงสุด 8 ล็อตทำเป็น **ดริปแบ็ก** เพิ่ม (สินค้าแยก type `drip_bag`)

## Data (โมดูลนี้เป็นเจ้าของ)

```
origins 1 ──< products >── product_flavor_notes ──< flavor_notes
                │ 1
                ├──< product_variants
                └── 1 cupping_scores        (1:1)
```

**origins** — แยกตารางเพื่อไม่เก็บประเทศ/ภูมิภาคซ้ำ (3NF)

| คอลัมน์ | ชนิด |
|---|---|
| id | bigint PK |
| country | varchar(60) |
| region | varchar(100) |
| UNIQUE(country, region) | |

**products**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| origin_id | FK → origins | |
| name | varchar(120) | เช่น "Guji Hambela" |
| slug | varchar(140) UNIQUE | ใช้ใน URL |
| nickname | varchar(60) | ข้อความลายมือบนการ์ด |
| type | enum('bean','drip_bag') | |
| farm_name | varchar(150) NULL | |
| variety | varchar(60) NULL | สายพันธุ์ เช่น Bourbon, Typica |
| process | enum('washed','natural','honey','semi_washed','other') | |
| altitude_m | smallint unsigned NULL | |
| roast_level | enum('light','medium','dark') | |
| roast_date | date | |
| description | text NULL | |
| is_active | boolean default true | ซ่อนสินค้าโดยไม่ลบ |
| cqi_ref | varchar(20) NULL | เลขแถวต้นทางใน CQI เพื่อตรวจย้อนได้ |
| timestamps | | |

**cupping_scores** — 1:1 กับ products (มีเฉพาะสินค้าที่มาจาก CQI)

| คอลัมน์ | ชนิด |
|---|---|
| product_id | PK + FK → products (cascade delete) |
| aroma, flavor, aftertaste, acidity, body, balance, uniformity, clean_cup, sweetness, cupper_points | decimal(4,2) |
| total_points | decimal(5,2) |
| graded_on | date NULL |

**product_variants**

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| product_id | FK → products | |
| size | enum('100g','250g','500g','sachet') | เมล็ด = 3 ขนาด, ดริปแบ็ก = `sachet` (1 ซอง) |
| weight_g | smallint unsigned | ดริปแบ็ก 1 ซอง = 12g |
| price | decimal(10,2) | |
| stock | int unsigned | |
| sku | varchar(40) UNIQUE | |
| UNIQUE(product_id, size) | | |

การบดไม่อยู่ที่นี่ (ตัดสินใจแล้ว — เลือกตอนใส่ตะกร้า) และดริปแบ็กไม่มีตัวเลือกการบด

**flavor_notes** + **product_flavor_notes** (M:N)

| flavor_notes | ชนิด |
|---|---|
| id | bigint PK |
| name | varchar(40) UNIQUE เช่น Jasmine, Blueberry, Hazelnut |
| category | enum('fruity','floral','chocolate','nutty','caramel') |

`product_flavor_notes(product_id, flavor_note_id)` PK คู่, ไม่มีคอลัมน์อื่น

Seed โน้ต ~20 ตัว (5 กลุ่ม × ~4) — `taste-profile` จะใช้ `category` เป็นตัวเลือกใน Taste Quiz

## หน้าจอ

### `/products` — รายการสินค้า

```
[ หัวข้อ: สินค้า  all beans ]                                   [ เรียง: คั่วล่าสุด ▾ ]
กลุ่มรส:  (Fruity) (Floral) (Chocolate) (Nutty) (Caramel)
ระดับคั่ว: (Light) (Medium) (Dark)      ประเภท: (เมล็ด) (ดริปแบ็ก)      ประเทศ: [ ทั้งหมด ▾ ]
พบ 23 รายการ · ล้างตัวกรอง
┌card┐ ┌card┐ ┌card┐ ┌card┐          4 คอลัมน์ (lg) · 2 (sm) · 1 (มือถือ)
...                                   12 ชิ้น/หน้า + pagination
```

- ตัวกรองเป็นฟอร์ม `GET` (ไม่ใช้ JS framework): chip = checkbox ที่ถูกจัดหน้าตา, กดแล้วส่งฟอร์มทันทีด้วย Alpine `@change="$el.form.submit()"` — ถ้าปิด JS ยังมีปุ่ม "กรอง"
- query: `?flavor[]=fruity&roast[]=light&type=bean&country=Ethiopia&sort=newest|price_asc|price_desc|score&page=2`
- กลุ่มรสหลายตัว = OR ภายในกลุ่มเดียวกัน, ต่างกลุ่มตัวกรอง = AND
- ค่าที่ไม่รู้จักใน query ถูกเพิกเฉย (ไม่ error)
- ไม่พบสินค้า → `<x-empty-state>` + ปุ่มล้างตัวกรอง
- แสดงเฉพาะ `is_active = true`

### Product card (`<x-product-card :product :match="null" :featured="false">`)

| ส่วน | เนื้อหา |
|---|---|
| บรรทัดเล็ก | ประเทศ · วิธีแปรรูป (หรือ "ดริปแบ็ก") |
| ชื่อ | `name` (sans) + `nickname` (script, บรรทัดใหม่) |
| ภาพ | `<x-illustrations.bean-bag>` หรือ `drip-bag` ตาม type |
| โน้ต | ชื่อโน้ตคั่นด้วย " · " สูงสุด 3 ตัว |
| ระดับคั่ว | chip เล็ก Light / Medium / Dark |
| แถวล่าง (เส้นประคั่น) | ราคาเริ่มต้น "เริ่ม ฿190" เป็นป้ายลายมือเอียง · ลิงก์ "ดูเมล็ด ›" |
| มุมขวาบน | match badge — แสดงเมื่อส่ง `match` เข้ามาเท่านั้น (taste-profile จะส่ง) |
| hover / `featured` | กลับสีเป็นพื้นโคบอลต์ |
| หมด | ทุก variant stock = 0 → ป้าย "หมดชั่วคราว" และการ์ดจางลง |

ทั้งการ์ดคลิกได้ ไปหน้า `/products/{slug}`

### `/products/{slug}` — รายละเอียด

```
[ภาพวาดใหญ่ในกล่องโคบอลต์]   ประเทศ · ภูมิภาค
                             ชื่อ  + nickname (script)
                             (Light)  คั่วเมื่อ 12 ต.ค. 2026
                             โน้ต: Jasmine · Bergamot · Peach
                             ขนาด: (100g ฿190) (250g ฿440) (500g ฿800)   ← radio chips, สต็อกหมด = disabled
                             [ ใส่ตะกร้า ]  ← ordering ทำงานจริง; ตอนนี้ guest → ไป login, member → disabled "เร็ว ๆ นี้"
──────────────────────────────────────────────
ข้อมูลล็อต (ตาราง 2 คอลัมน์): ฟาร์ม · สายพันธุ์ · ความสูง · วิธีแปรรูป · ภูมิภาค
คะแนน cupping (CQI) 84.25/100: แถบแนวนอน 6 ด้าน (aroma, flavor, aftertaste, acidity, body, balance) สเกล 6–10
"เมล็ดที่คล้ายกัน": 4 การ์ดที่มีโน้ตกลุ่มเดียวกันมากที่สุด
```

- slug ไม่พบ หรือ `is_active = false` → 404
- ไม่มี cupping_scores → ซ่อนส่วนคะแนน

### หน้าแรก — "เมล็ดล็อตใหม่"
แทน empty state เดิมด้วยการ์ด 4 ชิ้น roast_date ล่าสุด (active) + ลิงก์ "ดูทั้งหมด →" ไป `/products?sort=newest`

## Code Style

- Model scopes สำหรับตัวกรอง เพื่อให้ `taste-profile`/`admin` ใช้ต่อได้:

```php
// app/Models/Product.php
public function scopeActive(Builder $query): void
{
    $query->where('is_active', true);
}

public function scopeWithFlavorCategories(Builder $query, array $categories): void
{
    $query->whereHas('flavorNotes', fn (Builder $q) => $q->whereIn('category', $categories));
}

// app/Http/Controllers/ProductController.php
public function index(ProductFilterRequest $request): View
{
    $products = Product::query()
        ->active()
        ->filter($request->filters())   // ใช้ scopes ด้านบนตามที่มีใน query
        ->with(['origin', 'flavorNotes', 'variants'])
        ->withMin('variants', 'price')
        ->paginate(12)
        ->withQueryString();

    return view('products.index', compact('products'));
}
```

- Enums: `ProductType`, `ProcessMethod`, `RoastLevel`, `BagSize`, `FlavorCategory` (มี `label()` ภาษาไทย/อังกฤษสำหรับ UI)
- ป้องกัน N+1: หน้า list ต้อง eager load และใช้ `withMin` ไม่วนหา min price ใน Blade
- ราคาแสดงเป็น `฿` + number_format ไม่มีทศนิยมถ้าเป็นจำนวนเต็ม

## Testing Strategy

`tests/Feature/Catalog/` + factories ของทุก model (`ProductFactory` มี state `dripBag()`, `inactive()`, `withVariants()`)

| เทส | ตรวจอะไร |
|---|---|
| ProductListTest | เห็นเฉพาะ active · paginate 12 · กรองกลุ่มรส (OR) · กรองระดับคั่ว · กรองประเภท · กรองประเทศ · กรองหลายแบบพร้อมกัน (AND) · query แปลก ๆ ไม่ error · เรียงตามราคา/คะแนน/คั่วล่าสุด · ไม่พบ → empty state |
| ProductShowTest | แสดงข้อมูลล็อต + variants + โน้ต · slug ผิด 404 · inactive 404 · ไม่มี cupping → ไม่มีส่วนคะแนน · ปุ่มใส่ตะกร้าของ guest ลิงก์ไป login |
| HomeNewLotsTest | หน้าแรกแสดง 4 ตัวคั่วล่าสุด ไม่รวม inactive |
| CatalogSeederTest | seed แล้วได้ 40 เมล็ด + 8 ดริปแบ็ก · เมล็ดมี 3 variants · ดริปแบ็กมี 1 variant (`sachet`) · ทุกสินค้ามีโน้ต 2–4 ตัว · ทุกสินค้า CQI มี cupping_scores · มีสินค้าจากไทย ≥ 1 · seed ซ้ำได้ผลเท่าเดิม |
| SchemaTest | unique (product_id, size), unique slug, ลบ product แล้ว cupping_scores/variants/pivot ถูกลบตาม |

## Boundaries

- **Always:** ข้อมูล CQI ใช้ตามจริง ไม่แต่งตัวเลขคะแนน · ฟิลด์ที่แต่งขึ้น (โน้ต, คั่ว, ราคา, วันคั่ว) สร้างจากกฎใน seeder ที่เดียวและมีคอมเมนต์ระบุว่าเป็นข้อมูลสาธิต · แนบ MIT notice
- **Ask first:** เพิ่ม package (เช่น ตัวทำ slug, chart library) · เปลี่ยนจำนวน/เกณฑ์คัดล็อต · เปลี่ยนโครงสร้างตารางหลังโมดูลอื่นเริ่มใช้
- **Never:** commit ไฟล์ CSV เต็ม 650KB โดยไม่จำเป็น (เก็บเฉพาะ 40 แถวที่คัด) · อ้างว่าโน้ตรส/ราคาเป็นข้อมูลจาก CQI

## Success Criteria

1. `migrate:fresh --seed` ได้ 40 เมล็ด + 8 ดริปแบ็ก จากหลายประเทศ (มีไทย) พร้อม variants, โน้ต, cupping scores
2. `/products` แสดงการ์ดตาม DESIGN.md, กรอง 4 แบบ + เรียง 4 แบบ ทำงานร่วมกันและคงค่าตอนเปลี่ยนหน้า
3. หน้าสินค้าแสดงข้อมูลล็อต, ขนาด/ราคา/สต็อก, คะแนน cupping, เมล็ดที่คล้ายกัน; slug ผิด/inactive = 404
4. หน้าแรกแสดง 4 เมล็ดคั่วล่าสุด
5. หน้า list ใช้ query ไม่เกิน ~6 ครั้ง (ไม่มี N+1) — ตรวจด้วยเทส `DB::getQueryLog()`
6. ใช้งานบนมือถือได้ (การ์ด 1 คอลัมน์, ตัวกรองเลื่อนแนวนอนได้)
7. `sail artisan test` ผ่านทั้งหมด, `sail pint --test` สะอาด

## Open Questions

1. **ข้อมูลที่ CQI ไม่มี** (โน้ตรส, ระดับคั่ว, ราคา, วันคั่ว) — กำหนดด้วยกฎตามตารางด้านบนโอเคไหม? หรืออยากกำหนดราคา/โน้ตเองบางตัว
2. **จำนวนสินค้า** 40 เมล็ด + 8 ดริปแบ็ก พอสำหรับ demo ไหม?
3. **ดริปแบ็กขายทีละซอง** (โปร "ซื้อ 10 แถม 2" นับจำนวนซองในตะกร้า) — ตรงกับที่คิดไว้ไหม? หรืออยากขายเป็นกล่อง 10 ซอง
4. **แสดงคะแนน cupping จาก CQI** บนหน้าสินค้า (แถบ 6 ด้าน + คะแนนรวม) — อยากได้ไหม?
5. ชื่อสินค้าใช้ภาษาอังกฤษตามแหล่งปลูก (เช่น "Guji Hambela", "Chiang Rai Typica") ส่วนคำอธิบาย/ป้ายเป็นไทย — โอเคไหม?
