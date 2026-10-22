# Plan: community

> อ้างอิง [docs/specs/SPEC-community.md](../docs/specs/SPEC-community.md) · สถานะ: **รอรีวิว**

## ลำดับ

```
C1 Schema: wishlists (PK คู่), reviews (order_item_id UNIQUE, rating CHECK 1–5) + models + factories
 ├─ C2 Wish List: toggle POST/DELETE, หน้า /wishlist, หัวใจบนการ์ด/หน้าสินค้า, ลิงก์ในเมนู
 └─ C3 รีวิว: ReviewPolicy, SubmitReview, ฟอร์มสร้าง/แก้/ลบ, ปุ่มในหน้าออเดอร์
     └─ C4 Rating: scopeWithRating (subquery), ★ บนการ์ด, สรุป + ตามวิธีชง + กรอง ในหน้าสินค้า, เรียงตามคะแนน
         └─ C5 CommunitySeeder (รีวิว + Wish List ตัวอย่าง)
             └─ C6 Checkpoint
```

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| reviews → สินค้า/ผู้รีวิว | JOIN ผ่าน order_items (ไม่เก็บซ้ำ) | ตาม 3NF ที่ตกลง |
| ค่าเฉลี่ยบนการ์ด | correlated subquery `rating_avg`, `rating_count` ใน `addSelect` | 1 query ต่อหน้าเหมือนเดิม |
| ค่าเฉลี่ยตามวิธีชง | `GROUP BY brew_method` บน `Review::forProduct()` | query เดียวต่อหน้าสินค้า |
| rating 1–5 | validation + CHECK constraint ใน MySQL 8 | ป้องกันทั้งสองชั้น |
| order_item ที่ยังไม่รีวิวในหน้าออเดอร์ | eager load `items.review` | ไม่มี N+1 |
| Wish List toggle | ฟอร์ม POST/DELETE + redirect back (ไม่ต้องมี JS) | ทำงานกับทุกการ์ด, เทสง่าย |
| สถานะหัวใจบนการ์ด | โหลด id ใน Wish List ของผู้ใช้ครั้งเดียวต่อ request (cache แบบ PromotionBadges) | ไม่เพิ่ม query ตามจำนวนการ์ด |
| ชื่อผู้รีวิว | `User::shortName()` → "สมชาย ใ." | ใช้ซ้ำใน sales-report ได้ |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| ปุ่มหัวใจอยู่ในการ์ดที่เป็น `<a>` ทั้งใบ (form ใน a ไม่ valid) | ย้ายการ์ดเป็น `<article>` + ลิงก์ stretched หรือวางหัวใจนอก `<a>` ด้วย absolute |
| JOIN 3 ตารางบนหน้า Products ช้า | index ที่มีอยู่ครบ, ข้อมูลเล็ก; ตรวจด้วย query log |
| seeder รีวิวไม่ตรงกับออเดอร์ delivered เมื่อวันที่เปลี่ยน | สร้างจากออเดอร์ delivered จริงหลัง OrderSeeder, seed RNG คงที่ |

## Checkpoints
- หลัง C3: เขียน/แก้/ลบรีวิวในเบราว์เซอร์จากหน้าออเดอร์
- C6: Success Criteria 1–6 + screenshot
