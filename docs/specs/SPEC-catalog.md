# Spec: catalog

> โมดูลที่ 2 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **อนุมัติแล้ว (2026-10-21)** · Depends on: foundation · ผู้ใช้ต่อ: ordering, taste-profile, promotions, loyalty, community, admin, sales-report

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

**สิ่งที่ CQI ไม่มี** ต้องสร้างขึ้นตามวิธีที่ผู้ใช้กำหนด (2026-10-21) — ทุกค่าสุ่มด้วย seed คงที่ต่อล็อต (`cqi_ref`) จึง seed ซ้ำได้ผลเท่าเดิม และ seeder มีคอมเมนต์ระบุว่าเป็นข้อมูลสาธิต

> หมายเหตุ: คอลัมน์ `Color` ใน CQI คือ **สีของสารกาแฟ (green bean)** เช่น Green / Bluish-Green ไม่ใช่ระดับคั่ว จึงไม่ใช้กำหนดระดับคั่ว และคะแนน cupping ของ CQI ชิมจากการคั่วมาตรฐาน SCA (คั่วอ่อน) ระดับคั่วที่ได้จึงเป็น "แนวโน้มสาธิต"

**1) โน้ตรสชาติ — WCR Sensory Lexicon + Score-Based Probabilistic Mapping**

โครงสร้างโน้ตอิง World Coffee Research Sensory Lexicon (หมวด → หมวดย่อย → โน้ต) และใช้คะแนน `Flavor` แบ่ง tier ความซับซ้อน แล้วสุ่มหมวดย่อยตามน้ำหนัก:

| Process | Flavor tier | น้ำหนักหมวดย่อย |
|---|---|---|
| Natural | < 8.0 | Dried fruit 60% · Cocoa/Nutty 30% · Fermented 10% *(ผู้ใช้กำหนด)* |
| Natural | 8.0–8.49 | Berry 45% · Dried fruit 30% · Cocoa 25% *(เสนอเติมช่วงกลาง)* |
| Natural | ≥ 8.5 | Tropical fruit 50% · Berry/Floral 40% · Stone fruit 10% *(ผู้ใช้กำหนด)* |
| Washed | < 8.0 | Cocoa/Nutty 45% · Brown sugar 35% · Citrus 20% *(เสนอ)* |
| Washed | 8.0–8.49 | Citrus 40% · Stone fruit 30% · Brown sugar 30% *(เสนอ)* |
| Washed | ≥ 8.5 | Floral 50% · Citrus 30% · Berry 20% *(เสนอ)* |
| Honey / Semi-washed / Other | < 8.0 | Brown sugar 50% · Nutty 30% · Stone fruit 20% *(เสนอ)* |
| Honey / Semi-washed / Other | ≥ 8.0 | Brown sugar 40% · Stone fruit 35% · Tropical 25% *(เสนอ)* |

จำนวนโน้ต: tier < 8.0 = 2 โน้ต · 8.0–8.49 = 3 · ≥ 8.5 = 4 (ยิ่งคะแนนสูงยิ่งซับซ้อน) — สุ่มหมวดย่อยตามน้ำหนักแล้วหยิบโน้ตในหมวดนั้นที่ยังไม่ซ้ำ

**2) ระดับคั่ว — Acidity : Body ratio**

`ratio = Acidity / Body` → `> 1.05` Light · `0.95–1.05` Medium · `< 0.95` Dark *(ผู้ใช้กำหนด)*

ใน 596 ล็อตที่ผ่านเกณฑ์ ได้ Light 37 · Medium 531 · Dark 28 — เกือบทั้งหมดเป็น Medium จึง **คัด 40 ล็อตแบบแบ่งชั้น**: Light 13 · Medium 14 · Dark 13 (คงสูตรเดิม แต่ demo มีครบทุกระดับให้กรองได้)

**ผลจริงหลังทำความสะอาด (C1):** หลังตัดล็อตที่ภูมิภาคว่าง/ซ้ำ และล็อตที่ระบุประเทศผิด (CQI มี "United States" ที่ภูมิภาคเป็น Antioquia ของโคลอมเบีย) เหลือล็อตแนวโน้ม Dark แค่ 10 → ได้ **Light 13 · Medium 17 · Dark 10** จาก 19 ประเทศ (ไทย 3) — สายพันธุ์/ความสูงว่างได้ (คอลัมน์ nullable)

**3) ราคา — Hedonic (non-linear) price model** *(ผู้ใช้กำหนดรูปแบบ, ค่าคงที่เสนอ)*

```
ราคา 250g = 280 + (Total − 80)^2.0 × 6 × M_process × M_origin   → ปัดเป็นหลักสิบ
M_process: Natural 1.2 · Honey 1.15 · Other 1.05 · Washed / Semi-washed 1.0
M_origin:  Ethiopia, Kenya, Panama 1.2 · United States (Hawaii) 1.4 · อื่น ๆ 1.0
100g = 250g × 0.45 · 500g = 250g × 1.85 · ดริปแบ็ก 1 ซอง (12g) = 250g × 12/250 × 1.4
```
ตัวอย่าง (Washed, origin 1.0): 82 → ฿300 · 84 → ฿380 · 86 → ฿500 · 88 → ฿660 · 90 → ฿880 ต่อ 250g

**4) วันคั่ว — Inventory lifecycle & resting period**

สุ่มอายุหลังคั่วแบบ Normal distribution ค่าเฉลี่ย 15 วัน, SD 6 วัน, ตัดให้อยู่ในช่วง 7–60 วัน → `roast_date = วันรัน seeder − อายุ`

**ไม่ทำในรอบนี้**: KNN imputation จาก Coffee Review dataset (ยังไม่มีชุดข้อมูลที่ license ชัดเจนในมือ) — เก็บเป็นงานต่อยอด

ล็อตที่คะแนนสูงสุด 8 ล็อตทำเป็น **ดริปแบ็ก** เพิ่ม (สินค้าแยก type `drip_bag`)

## Data (โมดูลนี้เป็นเจ้าของ)

```
origins 1 ──< products >── product_flavor_notes ──< flavor_notes >── 1 flavor_categories ─┐
                │ 1                                                            ^ parent_id ──┘ (recursive)
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

**flavor_categories** — ลำดับชั้น WCR แบบอ้างอิงตัวเอง (unary / recursive relationship)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| parent_id | FK → flavor_categories NULL | NULL = หมวดหลัก |
| slug | varchar(40) UNIQUE | เช่น `fruity`, `berry` |
| name | varchar(60) | |

หมวดหลัก (ใช้เป็นตัวกรองและตัวเลือก Taste Quiz): **Fruity · Floral · Sweet · Nutty/Cocoa · Sour/Fermented**
หมวดย่อย: Berry, Dried fruit, Citrus, Stone fruit, Tropical fruit (← Fruity) · Blossom, Black tea (← Floral) · Brown sugar, Honey (← Sweet) · Nutty, Cocoa (← Nutty/Cocoa) · Fermented (← Sour/Fermented)

**flavor_notes** + **product_flavor_notes** (M:N)

| flavor_notes | ชนิด |
|---|---|
| id | bigint PK |
| flavor_category_id | FK → flavor_categories (หมวดย่อย) |
| name | varchar(40) UNIQUE เช่น Blueberry, Raisin, Jasmine, Mango, Peach |

`product_flavor_notes(product_id, flavor_note_id)` PK คู่

Seed โน้ต 36 ตัว (~2–3 ต่อหมวดย่อย) — ตัวกรอง "กลุ่มรส" ใช้หมวดหลัก (join ผ่านหมวดย่อย → parent)

## หน้าจอ

### `/products` — รายการสินค้า

```
[ หัวข้อ: สินค้า  all beans ]                                   [ เรียง: คั่วล่าสุด ▾ ]
กลุ่มรส:  (Fruity) (Floral) (Sweet) (Nutty/Cocoa) (Fermented)
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
    $query->whereHas('flavorNotes.category.parent', fn (Builder $q) => $q->whereIn('slug', $categories));
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

- Enums: `ProductType`, `ProcessMethod`, `RoastLevel`, `BagSize` (มี `label()` สำหรับ UI) — หมวดรสเป็นตาราง ไม่ใช่ enum
- ป้องกัน N+1: หน้า list ต้อง eager load และใช้ `withMin` ไม่วนหา min price ใน Blade
- ราคาแสดงเป็น `฿` + number_format ไม่มีทศนิยมถ้าเป็นจำนวนเต็ม

## Testing Strategy

`tests/Feature/Catalog/` + factories ของทุก model (`ProductFactory` มี state `dripBag()`, `inactive()`, `withVariants()`)

| เทส | ตรวจอะไร |
|---|---|
| ProductListTest | เห็นเฉพาะ active · paginate 12 · กรองกลุ่มรส (OR) · กรองระดับคั่ว · กรองประเภท · กรองประเทศ · กรองหลายแบบพร้อมกัน (AND) · query แปลก ๆ ไม่ error · เรียงตามราคา/คะแนน/คั่วล่าสุด · ไม่พบ → empty state |
| ProductShowTest | แสดงข้อมูลล็อต + variants + โน้ต · slug ผิด 404 · inactive 404 · ไม่มี cupping → ไม่มีส่วนคะแนน · ปุ่มใส่ตะกร้าของ guest ลิงก์ไป login |
| HomeNewLotsTest | หน้าแรกแสดง 4 ตัวคั่วล่าสุด ไม่รวม inactive |
| CatalogSeederTest | seed แล้วได้ 40 เมล็ด + 8 ดริปแบ็ก · เมล็ดมี 3 variants · ดริปแบ็กมี 1 variant (`sachet`) · มีครบ 3 ระดับคั่ว · มีสินค้าจากไทย ≥ 1 · ทุกสินค้า CQI มี cupping_scores · seed ซ้ำได้ผลเท่าเดิม |
| CatalogRulesTest (unit) | จำนวนโน้ตตาม tier · ratio → ระดับคั่วที่ขอบ 0.95/1.05 · ราคาตามสูตร (ตัวอย่าง 82/86/90) · อายุคั่วอยู่ใน 7–60 วันเสมอ · seed เดียวกันได้ผลเดียวกัน |
| SchemaTest | unique (product_id, size), unique slug, ลบ product แล้ว cupping_scores/variants/pivot ถูกลบตาม |

## Boundaries

- **Always:** ข้อมูล CQI ใช้ตามจริง ไม่แต่งตัวเลขคะแนน · ฟิลด์ที่แต่งขึ้น (โน้ต, คั่ว, ราคา, วันคั่ว) สร้างจากกฎใน seeder ที่เดียวและมีคอมเมนต์ระบุว่าเป็นข้อมูลสาธิต · แนบ MIT notice
- **Ask first:** เพิ่ม package (เช่น ตัวทำ slug, chart library) · เปลี่ยนจำนวน/เกณฑ์คัดล็อต · เปลี่ยนโครงสร้างตารางหลังโมดูลอื่นเริ่มใช้
- **Never:** commit ไฟล์ CSV เต็ม 650KB โดยไม่จำเป็น (เก็บเฉพาะ 40 แถวที่คัด) · อ้างว่าโน้ตรส/ราคาเป็นข้อมูลจาก CQI

## Success Criteria

1. `migrate:fresh --seed` ได้ 40 เมล็ด + 8 ดริปแบ็ก จากหลายประเทศ (มีไทย) ครบ 3 ระดับคั่ว พร้อม variants, โน้ต, cupping scores
2. `/products` แสดงการ์ดตาม DESIGN.md, กรอง 4 แบบ + เรียง 4 แบบ ทำงานร่วมกันและคงค่าตอนเปลี่ยนหน้า
3. หน้าสินค้าแสดงข้อมูลล็อต, ขนาด/ราคา/สต็อก, คะแนน cupping, เมล็ดที่คล้ายกัน; slug ผิด/inactive = 404
4. หน้าแรกแสดง 4 เมล็ดคั่วล่าสุด
5. หน้า list ใช้ query ไม่เกิน ~6 ครั้ง (ไม่มี N+1) — ตรวจด้วยเทส `DB::getQueryLog()`
6. ใช้งานบนมือถือได้ (การ์ด 1 คอลัมน์, ตัวกรองเลื่อนแนวนอนได้)
7. `sail artisan test` ผ่านทั้งหมด, `sail pint --test` สะอาด

## Decisions (2026-10-21)

1. ข้อมูลที่ CQI ไม่มี ใช้วิธีของผู้ใช้ (WCR + probabilistic, Acidity:Body, Hedonic price, Normal roast age) — ค่าที่ผู้ใช้ไม่ได้ระบุ (ช่วงกลางของ tier, process อื่น, ค่าคงที่ราคา) ใช้ตามที่เสนอในตาราง ปรับได้ภายหลังที่ `app/Support/Catalog/DemoDataRules.php` ไฟล์เดียว
2. 40 เมล็ด + 8 ดริปแบ็ก (คัดแบบแบ่งชั้นตามระดับคั่ว)
3. ดริปแบ็กขายทีละซอง
4. แสดงคะแนน cupping บนหน้าสินค้า (ร้าน specialty ส่วนใหญ่แสดงคะแนน SCA/cupping)
5. ชื่อสินค้าอังกฤษ, คำอธิบาย/ป้ายไทย
