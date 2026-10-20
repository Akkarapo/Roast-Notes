# Plan: foundation

> อ้างอิง [docs/specs/SPEC-foundation.md](../docs/specs/SPEC-foundation.md) · สถานะ: **เสร็จแล้ว (2026-10-21)**

## องค์ประกอบและลำดับ

```
T1 Scaffold (Laravel + Sail + Breeze)
 └─ T2 users schema + UserRole
     ├─ T3 Registration (phone/address, กัน role injection, redirect ตั้งค่าได้)
     ├─ T4 Admin area (middleware + routes/admin.php)
     │   └─ T5 Layout + navigation + หน้า placeholder ที่ต้องล็อกอิน
     └─ T6 Seeders (admin + members)
          └─ T7 Checkpoint: ตรวจ Success Criteria ครบ 7 ข้อ
```

ทำคนเดียว จึงทำตามลำดับ T1 → T7 แต่ละ task จบด้วยเทสผ่าน + 1 commit

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| redirect หลังสมัคร | `config('roastnotes.after_register_route')` (ค่าเริ่มต้น `home`) | Laravel 12 ไม่มี `RouteServiceProvider::HOME` แล้ว; `taste-profile` เปลี่ยนค่าเดียวที่ config |
| หน้า dashboard ของ Breeze | ลบออก, redirect หลัง login ไป `home` | เว็บร้านค้าไม่มี dashboard ฝั่ง member |
| หน้า profile ของ Breeze | คงไว้ที่ `/member` (แก้ชื่อ/อีเมล/รหัสผ่าน + เพิ่ม phone/address) | `ordering` จะเติมประวัติสั่งซื้อในหน้านี้ |
| admin routes | ไฟล์ `routes/admin.php` ลงทะเบียนใน `bootstrap/app.php` ด้วย prefix `admin`, name `admin.`, middleware `web, auth, admin` | แยกชัด โมดูล `admin` เติม route ได้ที่เดียว |
| ทดสอบ | MySQL `testing` ของ Sail + `RefreshDatabase` | ใช้ engine เดียวกับ production |

## ความเสี่ยง

| ความเสี่ยง | ผลกระทบ | วิธีลด |
|---|---|---|
| โปรเจคอยู่บนไดรฟ์ D: ของ Windows → Sail ผ่าน WSL2 ช้า (I/O ข้าม filesystem) | หน้าเว็บโหลดช้า 1–3 วิ ระหว่าง dev | ใช้ได้สำหรับงานนี้; ถ้าช้าเกินไป ย้าย clone ไปไว้ใน `~/` ของ WSL |
| Line ending CRLF จาก Windows ทำให้สคริปต์ `sail` / shell พัง | `sail up` error `bad interpreter` | เพิ่ม `.gitattributes` บังคับ `eol=lf` ใน T1 |
| พอร์ต 80 / 3306 ชนกับ XAMPP หรือ MySQL ในเครื่อง | Sail ขึ้นไม่ได้ | ตั้ง `APP_PORT` / `FORWARD_DB_PORT` ใน `.env.example` เป็น 8080 / 3307 |

## Verification checkpoints

- หลัง T1: `sail up -d` + `sail artisan test` ผ่าน (เทสของ Breeze)
- หลัง T5: เปิดเว็บด้วยมือ ลองสมัคร/ล็อกอิน/เข้า `/admin` ทั้งสอง role
- T7: ไล่ Success Criteria 1–7 ใน spec ทีละข้อ
