# Plan: catalog

> อ้างอิง [docs/specs/SPEC-catalog.md](../docs/specs/SPEC-catalog.md) · หน้าตา [DESIGN.md](../docs/specs/DESIGN.md) · สถานะ: **รอรีวิว**
> งานโมดูลก่อนหน้าอยู่ที่ `tasks/archive/`

## องค์ประกอบและลำดับ

```
C1 คัดข้อมูล CQI (สคริปต์ + CSV 48 แถว + MIT notice)
C2 Schema: migrations + enums
 └─ C3 Models + relations + factories
     ├─ C4 DemoDataRules (โน้ต / คั่ว / ราคา / วันคั่ว) — pure PHP + unit tests
     │   └─ C5 Seeders: หมวดรส WCR + สินค้าจาก CSV  ← ต้องมี C1, C4
     ├─ C6 หน้า /products: ตัวกรอง + เรียง + การ์ด
     │   ├─ C7 หน้า /products/{slug}: ข้อมูลล็อต + cupping + เมล็ดที่คล้ายกัน
     │   └─ C8 หน้าแรก "เมล็ดล็อตใหม่"
     └──────── C9 Checkpoint
```

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| คัดล็อต | Python script ใน `database/data/select_cqi.py` รันครั้งเดียว ผลลัพธ์ commit เป็น CSV | seeder ไม่ต้องพึ่งไฟล์ 650KB / network; ทำซ้ำได้ |
| การสุ่ม | `mt_srand(crc32(cqi_ref))` ต่อสินค้า ใน DemoDataRules | ผลคงที่ทุกครั้งที่ seed, ทดสอบได้ |
| Normal distribution | Box–Muller จาก RNG ข้างบน | ไม่ต้องเพิ่ม package |
| slug | `Str::slug(name)` + เติม `-2` ถ้าซ้ำ | ไม่ต้องเพิ่ม package |
| ตัวกรองหมวดรส | `whereHas('flavorNotes.category.parent')` ด้วย slug หมวดหลัก | หมวดเป็นตาราง recursive |
| ราคาเริ่มต้นบนการ์ด | `withMin('variants', 'price')` | กัน N+1 |
| เมล็ดที่คล้ายกัน | นับหมวดหลักที่ตรงกัน (subquery) เรียงมาก→น้อย, ตัดตัวเอง, 4 ตัว | ไม่ต้องคำนวณใน PHP |
| แถบ cupping | div ความกว้าง % (สเกล 6–10) | ไม่ต้องใช้ chart library |
| ปุ่มใส่ตะกร้า | guest → ลิงก์ login; member → ปุ่ม disabled "เร็ว ๆ นี้" | ordering จะเปลี่ยนเป็นฟอร์มจริง |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| ข้อมูล CQI สกปรก (region ตัวพิมพ์เล็ก/สะกดผิด เช่น "thialand", ชื่อซ้ำ) | สคริปต์ทำความสะอาด: title case, ตัดชื่อประเทศซ้ำในภูมิภาค, ตรวจด้วยตาก่อน commit CSV |
| ชื่อสินค้าซ้ำกัน (ภูมิภาค+สายพันธุ์เดียวกัน) | เติมชื่อฟาร์ม/เลขล็อต หรือ slug `-2` |
| ล็อต Light/Dark มีน้อย (37/28) | คัดแบบแบ่งชั้นตาม spec; ถ้าบางชั้นไม่พอให้รายงานก่อน |
| `whereHas` ซ้อน 3 ชั้นช้า | ข้อมูลแค่ 48 แถว ไม่เป็นปัญหา; ใส่ index ที่ FK ทุกตัว |
| MySQL `enum` ต่างจาก SQLite | เทสรันบน MySQL ของ Sail ตาม foundation |

## Verification checkpoints

- หลัง C5: `migrate:fresh --seed` แล้วดูตัวเลขใน DB (จำนวน, ระดับคั่ว, ราคา, โน้ต) ว่าสมเหตุสมผล
- หลัง C6: เปิด `/products` ลองกรองทุกแบบด้วยมือ + มือถือ
- C9: Success Criteria 1–7 + screenshot เทียบ DESIGN.md
