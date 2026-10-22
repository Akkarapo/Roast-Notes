# Todo: taste-profile

> อ้างอิง [plan.md](taste-profile-plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [x] **T1 — Schema + BrewMethod + TasteProfile helper**
  - Acceptance: migration `user_taste_preferences` + คอลัมน์ users 3 ตัว; enum `BrewMethod` (label, `defaultGrind()`); `User::tastePreferences()`, `hasTasteProfile()`, `likedCategoryIds()`, `defaultGrind()` (ใส่ใน User แทนคลาส TasteProfile แยก — เล็กพอ)
  - Verify: `tests/Feature/TasteProfile/SchemaTest.php`, `tests/Unit/TasteProfile/DefaultGrindTest.php`
- [x] **T2 — Taste Quiz**
  - Acceptance: GET/PUT `/taste-quiz` (3 ขั้น Alpine, ปิด JS ได้, เติมคำตอบเดิม, ข้ามได้); validate 1–3 หมวดหลัก; หลังสมัคร redirect มาที่นี่; `TasteProfileSeeder`
  - Verify: `tests/Feature/TasteProfile/TasteQuizTest.php`
- [x] **T3 — Match % + sort**
  - Acceptance: `scopeWithTasteMatch`, `matchPercent()`; badge บนการ์ดทุกหน้าที่มีการ์ด; sort `match` ใน Products (เฉพาะมี profile); query คงที่
  - Verify: `tests/Feature/TasteProfile/TasteMatchTest.php`, `ProductMatchDisplayTest.php`
- [x] **T4 — หน้าสินค้า**
  - Acceptance: badge ใหญ่ + "ตรงกับที่คุณชอบ: …" + "คั่วระดับที่คุณชอบ"; ไม่มี profile → ลิงก์ไป quiz; การบดเริ่มต้นตามวิธีชง
  - Verify: เพิ่มใน `ProductMatchDisplayTest.php`
- [x] **T5 — แนะนำสำหรับคุณ + ทางกลับมาทำ quiz**
  - Acceptance: หน้าแรก 4 อันดับ (ตัด 0%/inactive/หมด) หรือการ์ดชวนทำ quiz; เมนูผู้ใช้ "Taste Profile" + จุดเตือน; การ์ด Taste Profile ในหน้าสมาชิก
  - Verify: `tests/Feature/TasteProfile/RecommendationsTest.php`
- [x] **T6 — Checkpoint**
  - Acceptance: Success Criteria 1–7, screenshot, pint, CAPABILITY-MAP
