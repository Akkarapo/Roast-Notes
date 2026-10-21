# Spec: taste-profile

> โมดูลที่ 4 ของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md) · หน้าตาตาม [DESIGN.md](DESIGN.md)
> สถานะ: **อนุมัติแล้ว (2026-10-21)** · Depends on: foundation, catalog · ผู้ใช้ต่อ: community (วิธีชง), sales-report (รสที่ลูกค้าชอบ)

## Objective

จุดขายของ Roast & Notes: สมาชิกบอกรสที่ชอบผ่าน **Taste Quiz 3 ข้อ** หลังสมัคร แล้วทุกสินค้าแสดง **"เข้ากับลิ้นคุณ X%"** และหน้าแรกมี **"แนะนำสำหรับคุณ"**

**User stories**
- ในฐานะสมาชิกใหม่ สมัครเสร็จแล้วเข้า Taste Quiz ทันที ตอบ 3 ข้อแล้วไปหน้าแรกที่มีของแนะนำ (หรือกด "ข้ามไปก่อน")
- ในฐานะสมาชิก ฉันเห็น match % บนการ์ดสินค้าทุกใบ และหน้าสินค้าบอกว่าตรงกับรสไหนที่ฉันชอบ
- ในฐานะสมาชิก ฉันเรียงหน้า Products ตาม "เข้ากับลิ้นคุณ" ได้
- ในฐานะสมาชิก ฉันแก้ Taste Profile ได้จากหน้าข้อมูลสมาชิก
- ในฐานะผู้เยี่ยมชม / สมาชิกที่ยังไม่ทำ quiz ไม่เห็นเปอร์เซ็นต์ แต่เห็นชวน "ทำ Taste Quiz"

**นอกขอบเขต**: รีวิวตามวิธีชง (`community` — ใช้ enum `BrewMethod` ของโมดูลนี้), รายงานรสที่ลูกค้าชอบ (`sales-report`)

## Taste Quiz (3 ข้อ)

| ข้อ | คำถาม | ตัวเลือก | เก็บที่ |
|---|---|---|---|
| 1 | ชอบรสแบบไหน? (เลือก 1–3) | Fruity ผลไม้สดชื่น · Floral ดอกไม้/ชา · Sweet คาราเมล/น้ำผึ้ง · Nutty/Cocoa ถั่ว/ช็อกโกแลต · Sour/Fermented ไวน์/หมัก — ใช้ **หมวดหลัก WCR** จาก catalog | `user_taste_preferences` |
| 2 | ชอบระดับคั่วแบบไหน? | Light · Medium · Dark · ไม่แน่ใจ | `users.preferred_roast_level` (NULL = ไม่แน่ใจ) |
| 3 | ชงกาแฟวิธีไหนบ่อยที่สุด? | Pour-over · French press · Espresso · Moka pot · AeroPress · Cold brew · ดริปแบ็ก | `users.preferred_brew_method` |

- หน้าเดียว 3 ขั้นด้วย Alpine (ถัดไป / ย้อนกลับ, แถบความคืบหน้า) — ปิด JS ก็เห็นครบ 3 ข้อเป็นฟอร์มยาวและส่งได้
- ข้อ 1 ภาษาไทยมีคำอธิบายสั้น + ตัวอย่างโน้ต (เช่น Fruity: Blueberry, Peach, Lemon) ดึงจากตาราง flavor_notes
- "ข้ามไปก่อน" → ไปหน้าแรก · ทางกลับมาทำ quiz (สำหรับคนที่ยังไม่ทำ):
  1. **เมนูผู้ใช้** (dropdown ชื่อมุมขวาบน + เมนูมือถือ): "Taste Profile" — มีจุดเตือนเล็ก ๆ จนกว่าจะทำ
  2. **หน้าแรก**: การ์ด "ทำ Taste Quiz 3 ข้อ" อยู่ตำแหน่งเดียวกับ "แนะนำสำหรับคุณ"
  3. **หน้าข้อมูลสมาชิก**: การ์ด Taste Profile ว่าง + ปุ่ม "ทำ Taste Quiz"
  4. **หน้าสินค้า**: ตรงที่จะเป็น match badge แสดงลิงก์ "ดูว่าเข้ากับลิ้นคุณไหม →"
- เปิด quiz ซ้ำ = ฟอร์มเติมคำตอบเดิม (ใช้เป็นหน้าแก้ Taste Profile ด้วย)
- หลังสมัคร redirect ไป quiz โดยเปลี่ยน `config('roastnotes.after_register_route')` เป็น `taste-quiz.edit` (จุดเดียวที่ foundation เตรียมไว้)

## Data (โมดูลนี้เป็นเจ้าของ)

**user_taste_preferences** — M:N users ↔ flavor_categories (หมวดหลักเท่านั้น)

| คอลัมน์ | ชนิด |
|---|---|
| user_id | FK → users, cascade |
| flavor_category_id | FK → flavor_categories, cascade |
| PK(user_id, flavor_category_id) | |

**users** (เพิ่มคอลัมน์ผ่าน migration ของโมดูลนี้)

| คอลัมน์ | ชนิด |
|---|---|
| preferred_roast_level | enum('light','medium','dark') NULL |
| preferred_brew_method | enum('pour_over','french_press','espresso','moka_pot','aeropress','cold_brew','drip_bag') NULL |
| taste_quiz_completed_at | datetime NULL |

Enum ใหม่ `BrewMethod` (label ไทย/อังกฤษ) — `community` ใช้ต่อกับรีวิว

## Match % — "เข้ากับลิ้นคุณ X%"

ตามที่ตกลงไว้: นับจากโน้ตรสชาติที่ตรงกัน แต่ความชอบเก็บเป็น **หมวดหลัก** ส่วนสินค้ามี **โน้ตย่อย** จึงนับว่าโน้ตไหน "ตรง" เมื่อหมวดหลักของมันอยู่ในหมวดที่ชอบ:

```
match % = round( จำนวนโน้ตของเมล็ดที่อยู่ในหมวดที่ชอบ ÷ จำนวนโน้ตทั้งหมดของเมล็ด × 100 )
```

ตัวอย่าง: ชอบ Fruity + Floral · เมล็ดมี Raspberry (Fruity), Lemon (Fruity), Grapefruit (Fruity), Earl grey (Floral) → 4/4 = **100%** · เมล็ดมี Lemon (Fruity), Caramel (Sweet) → 1/2 = **50%**

- ระดับคั่วที่ชอบ **ไม่** อยู่ในเปอร์เซ็นต์ (ตามข้อตกลงเดิม) — ใช้เป็นตัวเรียงรองใน "แนะนำสำหรับคุณ"
- ไม่มีความชอบ → ไม่แสดงเปอร์เซ็นต์ (ไม่ใช่ 0%)
- คำนวณใน SQL ด้วย `withCount` 2 ตัว (โน้ตทั้งหมด, โน้ตที่ชอบ) ผ่าน scope `Product::withTasteMatch(User)` → ได้ทั้งตัวเลขบนการ์ดและเรียงหน้า Products ตาม match ได้โดยไม่มี N+1

## หน้าจอ

| Route | หน้า | สิทธิ์ |
|---|---|---|
| `GET /taste-quiz` | Taste Quiz / แก้ Taste Profile | member |
| `PUT /taste-quiz` | บันทึกคำตอบ | member |

### การแสดงผล
- **การ์ดสินค้า** (หน้า Products, หน้าแรก, เมล็ดที่คล้ายกัน): badge วงกลมมุมขวาบน "85% เข้ากับลิ้น" (ช่องที่ catalog เตรียมไว้) — เฉพาะสมาชิกที่มี Taste Profile
- **หน้าสินค้า**: badge ใหญ่บนกล่องภาพ + ประโยค "ตรงกับที่คุณชอบ: Fruity (Raspberry, Lemon) · Floral (Earl grey)" · ถ้าระดับคั่วตรงกับที่ชอบ เพิ่ม "คั่วระดับที่คุณชอบ"
- **หน้า Products**: ตัวเลือกเรียง "เข้ากับลิ้นคุณ" (แสดงเมื่อมี Taste Profile) — เรียง match % มาก→น้อย, รองด้วยระดับคั่วที่ชอบ, คั่วล่าสุด
- **หน้าแรก — "แนะนำสำหรับคุณ"**: แสดงเหนือ "ขายดี" เมื่อมี Taste Profile — 4 สินค้า active ที่มีของ เรียงเหมือนด้านบน ตัด match 0% ทิ้ง · ไม่มี Taste Profile → การ์ดชวน "ทำ Taste Quiz 3 ข้อ แล้วเราจะบอกว่าเมล็ดไหนเข้ากับลิ้นคุณ"
- **หน้าข้อมูลสมาชิก**: การ์ด "Taste Profile" แสดงหมวดที่ชอบ (chips), ระดับคั่ว, วิธีชง + ปุ่ม "แก้ไข"
- **ฟอร์มใส่ตะกร้า**: เลือกการบดเริ่มต้นตามวิธีชงที่ชอบ — Espresso/Moka pot → บดเอสเปรสโซ · Pour-over/AeroPress/Cold brew → บดดริป · French press/ดริปแบ็ก → เมล็ดเต็ม

## ข้อมูลตัวอย่าง

`TasteProfileSeeder`: member1 = Fruity + Floral, Light, Pour-over · member2 = Nutty/Cocoa + Sweet, Dark, Espresso · member3 = ยังไม่ทำ quiz (ไว้ demo หน้าชวนทำ quiz)

## Code Style

```php
// app/Models/Product.php
public function scopeWithTasteMatch(Builder $query, User $user): void
{
    $liked = $user->tastePreferences->pluck('id');   // top-level category ids

    $query->withCount([
        'flavorNotes as notes_total',
        'flavorNotes as notes_liked' => fn (Builder $q) => $q->whereHas(
            'category', fn (Builder $c) => $c->whereIn('parent_id', $liked)
        ),
    ]);
}

public function matchPercent(): ?int   // null when the scope was not applied or no notes
{
    return $this->notes_total ? (int) round($this->notes_liked / $this->notes_total * 100) : null;
}
```

- Logic อยู่ใน model scope + `app/Support/Taste/TasteProfile.php` (อ่านความชอบของ user, ตัดสินว่ามี profile ไหม, คำนวณ grind เริ่มต้น)
- Controller เดิม (Products/Home/Product show) เรียก `withTasteMatch` เฉพาะเมื่อ `TasteProfile::for($user)->exists()`

## Testing Strategy

`tests/Feature/TasteProfile/` + `tests/Unit/TasteProfile/`

| เทส | ตรวจอะไร |
|---|---|
| TasteQuizTest | สมัครแล้ว redirect ไป quiz · guest เข้าไม่ได้ · บันทึก 3 ข้อ · หมวดต้อง 1–3 และเป็นหมวดหลักเท่านั้น · "ไม่แน่ใจ" = NULL · แก้ไขแทนที่ของเดิม · ฟอร์มเติมคำตอบเดิม · ข้ามได้ |
| TasteMatchTest | ตัวอย่างในสเปก (100%, 50%) · ไม่มี profile = ไม่มี % · ปัดเศษ (1/3 = 33%, 2/3 = 67%) · คำนวณจาก SQL ตรงกับคำนวณมือ |
| ProductMatchDisplayTest | badge บนการ์ดเฉพาะสมาชิกที่มี profile · หน้าสินค้าแสดงหมวด/โน้ตที่ตรง · sort "เข้ากับลิ้นคุณ" เรียงถูก, ไม่มี profile = ตัวเลือกไม่แสดงและ query ไม่พัง · จำนวน query ยังคงที่ |
| RecommendationsTest | 4 อันดับตาม match → คั่วที่ชอบ → คั่วล่าสุด · ตัด 0% / inactive / หมด · ไม่มี profile = การ์ดชวนทำ quiz |
| DefaultGrindTest (unit) | วิธีชง → การบดเริ่มต้น |

## Boundaries

- **Always:** คำนวณ match ฝั่งเซิร์ฟเวอร์ใน SQL · ไม่แสดง 0% ให้คนที่ไม่มี profile · quiz ใช้หมวดจากฐานข้อมูล (ไม่ hard-code)
- **Ask first:** เปลี่ยนสูตร match (เช่น ให้น้ำหนักระดับคั่ว) · เปลี่ยนรายการวิธีชง (community ใช้ต่อ)
- **Never:** บังคับทำ quiz ก่อนใช้งานเว็บ · เก็บความชอบเป็นข้อความรวมในคอลัมน์เดียว (ต้องเป็นตาราง M:N)

## Success Criteria

1. สมัครสมาชิก → Taste Quiz → ตอบ 3 ข้อ → หน้าแรกมี "แนะนำสำหรับคุณ" 4 เมล็ด
2. การ์ดสินค้าทุกหน้าแสดง match % สำหรับสมาชิกที่มี profile และตัวเลขตรงสูตร
3. หน้าสินค้าบอกว่าโน้ตไหนตรงกับที่ชอบ
4. หน้า Products เรียง "เข้ากับลิ้นคุณ" ได้ ร่วมกับตัวกรองเดิม และ query ยังคงที่
5. แก้ Taste Profile จากหน้าสมาชิกได้ ผลเปลี่ยนทันที
6. ผู้เยี่ยมชม/ยังไม่ทำ quiz ไม่เห็นเปอร์เซ็นต์ และเห็นชวนทำ quiz
7. `sail artisan test` ผ่าน, `sail pint --test` สะอาด

## Decisions (2026-10-21)

1. คำถาม 3 ข้อ: รสที่ชอบ 1–3 หมวด · ระดับคั่ว (มี "ไม่แน่ใจ") · วิธีชง
2. สูตร match นับโน้ตตามหมวดหลักที่ชอบ ระดับคั่วไม่นับในเปอร์เซ็นต์
3. ข้าม quiz ได้ — กลับมาทำได้ 4 ทาง (เมนูผู้ใช้, หน้าแรก, หน้าสมาชิก, หน้าสินค้า)
4. การบดเริ่มต้นในฟอร์มใส่ตะกร้าตามวิธีชง
5. วิธีชง 7 แบบ ใช้ร่วมกับรีวิวใน community
