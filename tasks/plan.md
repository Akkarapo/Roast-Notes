# Plan: taste-profile

> อ้างอิง [docs/specs/SPEC-taste-profile.md](../docs/specs/SPEC-taste-profile.md) · สถานะ: **รอรีวิว**

## ลำดับ

```
T1 Schema + BrewMethod enum + relations + TasteProfile helper
 └─ T2 Taste Quiz (GET/PUT /taste-quiz) + redirect หลังสมัคร + seeder สมาชิกตัวอย่าง
     └─ T3 Match %: scope withTasteMatch + badge บนการ์ด (Products, หน้าแรก, คล้ายกัน) + sort "เข้ากับลิ้นคุณ"
         ├─ T4 หน้าสินค้า: badge ใหญ่ + โน้ตที่ตรง + การบดเริ่มต้น
         └─ T5 "แนะนำสำหรับคุณ" + ทางกลับมาทำ quiz 4 จุด + การ์ด Taste Profile ในหน้าสมาชิก
             └─ T6 Checkpoint
```

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| คำนวณ match | `withCount` 2 ตัวใน SQL, `Product::matchPercent()` อ่านผล | เรียง/แบ่งหน้าได้ใน SQL, ไม่มี N+1 |
| sort ตาม match | `orderByRaw('notes_liked / NULLIF(notes_total,0) DESC')` + roast ที่ชอบ + roast_date | MySQL คำนวณจาก alias ของ withCount ได้ |
| ส่งข้อมูลความชอบไปทุกหน้า | `TasteProfile::for($user)` โหลดครั้งเดียวต่อ request (memo) | ไม่ query ซ้ำในหลาย component |
| quiz หลายขั้น | ฟอร์มเดียว, Alpine ซ่อน/แสดงทีละขั้น | ส่งครั้งเดียว, ปิด JS ก็ใช้ได้ |
| จุดเตือนในเมนู | view composer เดิมของ navigation ส่ง `needsTasteQuiz` | ไม่แตะ controller |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| `orderByRaw` กับ alias ต่างกันใน SQLite/MySQL | เทสรันบน MySQL; ใช้ alias ที่ withCount สร้าง |
| สินค้าไม่มีโน้ต (หาร 0) | `NULLIF` + `matchPercent()` คืน null |
| ตัวกรองหมวดรสเดิมใช้ `flavorNotes.category.parent` ร่วมกับ withCount | เทส query count เดิมยังคงที่ |

## Checkpoints
- หลัง T2: สมัครใหม่ → quiz → บันทึก ด้วยมือ (เดสก์ท็อป + มือถือ)
- หลัง T3: เทียบ % บนการ์ดกับการคำนวณมือ 3 ตัวอย่าง
- T6: Success Criteria 1–7 + screenshot
