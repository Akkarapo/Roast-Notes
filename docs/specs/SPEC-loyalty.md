# Spec: loyalty

> โมดูลที่ 6 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **อนุมัติแล้ว (2026-10-23)** · Depends on: ordering (pricing pipeline, orders), catalog · ผู้ใช้ต่อ: admin (เปลี่ยนสถานะออเดอร์ → อัปเดตระดับ), sales-report

## Objective

Extra Feature **Loyalty Program** ของโจทย์: สมาชิกมี 3 ระดับตามยอดซื้อสะสม
**Bean → Barista → Roaster Master** แต่ละระดับได้ส่วนลดอัตโนมัติ และ Roaster Master ได้สิทธิ์ซื้อล็อตหายากก่อนคนอื่น

**User stories**
- ในฐานะสมาชิก ฉันเห็นระดับของตัวเอง ยอดสะสม และต้องซื้ออีกเท่าไรถึงขึ้นระดับถัดไป ในหน้า Member Info
- ในฐานะสมาชิก Barista/Roaster Master ส่วนลดระดับสมาชิกขึ้นในตะกร้าและ Checkout เองโดยไม่ต้องกรอกอะไร
- ในฐานะ Roaster Master ฉันซื้อล็อตหายากได้ก่อนวันที่เปิดขายทั่วไป
- ในฐานะสมาชิกระดับอื่น ฉันเห็นล็อตหายากและรู้ว่าจะซื้อได้วันไหน แต่ยังใส่ตะกร้าไม่ได้

**นอกขอบเขต**: หน้าแก้เกณฑ์ระดับ/ตั้งล็อตหายาก (`admin`), รายงานสมาชิกแต่ละระดับ (`sales-report`), แต้มสะสม/แลกของ

## Data

```
member_tiers 1 ──< users                (users.member_tier_id FK NOT NULL)
products.exclusive_until                (NULL = ไม่ใช่ล็อตหายาก / เปิดขายทั่วไปแล้ว)
order_discounts.source = 'loyalty'      (ใช้ตารางเดิมของ ordering, promotion_id = NULL)
```

**member_tiers** — ข้อมูลอ้างอิง ใส่ 3 แถวใน migration เลย (ระบบต้องมีเสมอ ไม่ใช่ข้อมูลตัวอย่าง)

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | tinyint PK | |
| slug | varchar(30) UNIQUE | `bean`, `barista`, `roaster-master` |
| name | varchar(50) | Bean / Barista / Roaster Master |
| min_spend | decimal(10,2) UNIQUE | ยอดสะสมขั้นต่ำ · Bean = 0 |
| discount_percent | tinyint unsigned | 0 / 5 / 10 |
| early_access | boolean | ซื้อล็อตหายากก่อนได้ไหม |
| timestamps | | |

| ระดับ | ยอดสะสม | ส่วนลด | ล็อตหายากก่อน |
|---|---|---|---|
| Bean | ฿0 | — | — |
| Barista | ≥ ฿25,000 | 5% | — |
| Roaster Master | ≥ ฿50,000 | 10% | ✅ |

> ทำไมไม่ใช่ ฿3,000 / ฿10,000 ตามสมมติฐานเดิม: ออเดอร์ตัวอย่างทำให้สมาชิกทั้ง 3 คนมียอด ฿23k–฿54k ถ้าใช้เกณฑ์เดิมทุกคนจะเป็น Roaster Master หมด
> เกณฑ์ใหม่ทำให้ตอน demo เห็นครบ 3 ระดับ: member3 ≈ ฿23,160 (Bean, อีก ~฿1,840 ขึ้น Barista) · member2 ≈ ฿30,770 (Barista) · member1 ≈ ฿54,350 (Roaster Master)

**users** — เพิ่ม `member_tier_id` FK → member_tiers, NOT NULL, default = Bean, `restrictOnDelete`

**products** — เพิ่ม `exclusive_until` datetime NULL: ก่อนเวลานี้เฉพาะระดับที่ `early_access = true` ใส่ตะกร้า/สั่งได้; หลังจากนั้นทุกคนซื้อได้

## กติกา

### ยอดสะสมและการขึ้นระดับ
- ยอดสะสม = ผลรวม `orders.total` ของออเดอร์สถานะ **delivered (ได้รับแล้ว)** เท่านั้น — ใช้ `total` ทั้งก้อน (ตรงกับที่ลูกค้าจ่ายจริง รวมค่าส่ง)
- คำนวณจาก query ทุกครั้ง ไม่เก็บยอดสะสมซ้ำใน users (ไม่มีข้อมูลซ้ำซ้อน)
- `member_tier_id` = ระดับสูงสุดที่ `min_spend ≤ ยอดสะสม` · อัปเดตผ่าน `RecalculateMemberTier` ซึ่งถูกเรียกเมื่อออเดอร์เปลี่ยนเป็น delivered (ฝั่ง admin ในโมดูลถัดไป) และตอน seed
- **ไม่ลดระดับ**: ถ้าคำนวณได้ต่ำกว่าเดิม (เช่น ข้อมูลถูกแก้) คงระดับเดิมไว้
- ทำไมเก็บ `member_tier_id` แทนคำนวณสด: ให้ ER มีความสัมพันธ์ member_tiers 1:N users ชัดเจน และ query ตะกร้าไม่ต้อง sum ออเดอร์ทุกครั้ง

### ส่วนลดระดับสมาชิก (กฎตัวที่สามใน pipeline: Buy X Get Y → คูปอง → **ระดับสมาชิก**)
- `LoyaltyTierRule`: ฐาน = ยอดสินค้า − ส่วนลดจากกฎก่อนหน้า · ส่วนลด = ฐาน × discount_percent ÷ 100 **ปัดลง**เป็นสตางค์
- ใช้กับสินค้าทุกบรรทัด (รวมล็อตหายาก) · ไม่ลดค่าส่ง · ผู้ใช้ระดับ Bean ไม่มีบรรทัดนี้
- ป้ายในสรุป: "สมาชิก Barista ลด 5%" · บันทึก `order_discounts` source `loyalty`
- ระดับที่ใช้ = ระดับ ณ ตอนกดยืนยัน (PlaceOrder อ่านใหม่ใน transaction)

### ล็อตหายาก (early access)
- สินค้าที่ `exclusive_until > now()`:
  - การ์ด + หน้าสินค้ามีป้าย "Roaster Master ซื้อก่อน · เปิดขายทั่วไป 6 พ.ย."
  - ระดับที่ `early_access` → ใส่ตะกร้าได้ปกติ
  - คนอื่น (และผู้เยี่ยมชม) → ปุ่มใส่ตะกร้าปิด พร้อมข้อความบอกวันเปิดขาย; ถ้ายิง POST มาเอง → 403 พร้อมข้อความเดียวกัน
- ตรวจซ้ำใน `PlaceOrder` (เผื่อใส่ตะกร้าไว้ก่อนแล้วสิทธิ์เปลี่ยน) → ไม่สร้างออเดอร์ กลับไปตะกร้าพร้อมเหตุผล
- หลัง `exclusive_until` ผ่านไป ป้ายหายเอง ทุกคนซื้อได้

## หน้าจอ

| ที่ไหน | เพิ่มอะไร |
|---|---|
| Member Info | การ์ดระดับสมาชิก: ชื่อระดับ + illustration · ยอดสะสม · progress bar "อีก ฿x ถึง Roaster Master" · สิทธิ์ของระดับนี้ · หมายเหตุ "นับเฉพาะออเดอร์ที่ได้รับแล้ว" |
| เมนูผู้ใช้ (navbar) | ป้ายระดับเล็ก ๆ ข้างชื่อ |
| ตะกร้า / Checkout / หน้าออเดอร์ | บรรทัด "สมาชิก Barista ลด 5%" (component order-summary รองรับอยู่แล้ว) · ถ้า Bean: ข้อความเล็ก "ซื้ออีก ฿x เพื่อเป็น Barista รับส่วนลด 5%" |
| การ์ด + หน้าสินค้า | ป้าย early access · ปุ่มใส่ตะกร้าปิดสำหรับคนที่ยังไม่มีสิทธิ์ |
| Products | ตัวกรอง "ล็อตหายาก" (ไม่บังคับ — ถ้าเวลาไม่พอตัดได้) |

## ข้อมูลตัวอย่าง

- `member_tiers` 3 แถวมาจาก migration
- `LoyaltySeeder` (หลัง OrderSeeder): เรียก `RecalculateMemberTier` ให้สมาชิกทุกคน → member1 Roaster Master, member2 Barista, member3 Bean
- ล็อตหายาก: เมล็ด 3 ตัวที่คะแนน cupping สูงสุด (Guji-Hambela Washed 90.58, Gimbowereda Natural 88.25, Yunnan Catimor 87.25) → `exclusive_until = วันนี้ + 14 วัน`

## Code Style

```php
// app/Support/Pricing/Rules/LoyaltyTierRule.php
public function apply(PricingContext $context): array
{
    $tier = $context->user?->memberTier;
    if (! $tier || $tier->discount_percent === 0) {
        return [];
    }
    $base = $context->subtotal - $context->discountedSoFar;          // สตางค์
    $amount = intdiv($base * $tier->discount_percent, 100);         // ปัดลง

    return $amount > 0 ? [new DiscountLine('loyalty', __('สมาชิก :tier ลด :percent%', [...]), $amount)] : [];
}
```

- `App\Support\Loyalty\LoyaltyStatus` (tier, spend, next tier, remaining) ใช้ร่วมกันใน Member Info, ตะกร้า, navbar
- `Product::isExclusiveNow()` + `ProductPolicy::purchase(User $user, Product $product)`
- ลงทะเบียนกฎใน `config('roastnotes.discount_rules')` ต่อจาก CouponRule (ช่องที่เว้นไว้แล้ว)

## Testing Strategy

`tests/Feature/Loyalty/`

| เทส | ตรวจอะไร |
|---|---|
| SchemaTest | migration มี 3 ระดับเรียงตาม min_spend · สมาชิกใหม่เป็น Bean · ลบ tier ที่มีคนใช้ไม่ได้ |
| RecalculateMemberTierTest | นับเฉพาะ delivered · ตรงเกณฑ์พอดีขึ้นระดับ · ไม่ลดระดับ · เปลี่ยน pending → delivered แล้วขึ้นระดับ |
| LoyaltyTierRuleTest | Bean ไม่มีส่วนลด · 5%/10% คิดจากยอดหลังหักโปร · ปัดลง · ลำดับ Buy X Get Y → คูปอง → ระดับ · ไม่ลดค่าส่ง |
| CheckoutLoyaltyTest | order_discounts มีบรรทัด loyalty · ตัวเลขตรงกันตะกร้า/Checkout/ออเดอร์ · ส่งฟรีคิดหลังหักส่วนลดทั้งหมด |
| EarlyAccessTest | Roaster Master ใส่ตะกร้าได้ · ระดับอื่น/ผู้เยี่ยมชมได้ 403 · PlaceOrder ปฏิเสธ · พ้นวันแล้วทุกคนซื้อได้ · ป้ายบนการ์ด |
| MemberInfoTierTest | แสดงระดับ ยอดสะสม ยอดที่ขาด · Roaster Master แสดงว่าระดับสูงสุดแล้ว |

## Boundaries

- **Always:** คิดเงินเป็นสตางค์ ปัดลง · ตรวจสิทธิ์ early access ซ้ำใน transaction · ยอดสะสมนับจาก delivered เท่านั้น
- **Ask first:** ลดระดับเมื่อยอดลด / นับยอดแบบรอบ 12 เดือน · ระบบแต้ม · ส่วนลดระดับไม่ซ้อนกับคูปอง
- **Never:** เก็บยอดสะสมซ้ำในตาราง users · ให้ฟอร์มส่ง tier/ส่วนลดมาเอง

## Success Criteria

1. หลัง seed: member1 = Roaster Master, member2 = Barista, member3 = Bean และ Member Info แสดงยอดสะสม + ยอดที่ขาดถูกต้อง
2. ตะกร้าของ Barista มีบรรทัด "สมาชิก Barista ลด 5%" คิดหลังหักโปร ตัวเลขตรงกันจนถึงหน้าออเดอร์
3. ออเดอร์ของ member3 เปลี่ยนเป็น delivered จนยอดถึง ฿25,000 → ขึ้น Barista
4. Roaster Master ซื้อล็อตหายากได้ คนอื่นเห็นป้ายวันเปิดขายแต่ใส่ตะกร้าไม่ได้ (UI + POST + PlaceOrder)
5. `sail artisan test` ผ่าน, `sail pint --test` สะอาด

## Decisions (2026-10-23)

1. เกณฑ์ Bean ฿0 / Barista ≥ ฿25,000 (5%) / Roaster Master ≥ ฿50,000 (10% + ล็อตหายากก่อน) นับเฉพาะออเดอร์ที่ได้รับแล้ว
2. ไม่ลดระดับ
3. ล็อตหายาก: คนที่ยังไม่มีสิทธิ์เห็นสินค้าได้ แต่ใส่ตะกร้าไม่ได้ จนถึงวันเปิดขายทั่วไป (Roaster Master ได้ก่อน 14 วัน)
