# Todo: foundation

> อ้างอิง [plan.md](plan.md) · ทุก task จบด้วย `sail artisan test` ผ่าน + 1 commit

- [ ] **T1 — Scaffold Laravel 12 + Sail (MySQL) + Breeze (Blade, PHPUnit)**
  - Acceptance: โปรเจครันด้วย `sail up -d`, เปิด `http://localhost:8080` ได้, เทสของ Breeze ผ่าน; มี `.gitattributes` (eol=lf), `.env.example` ตั้ง `APP_NAME="Roast & Notes"`, `APP_PORT=8080`, `FORWARD_DB_PORT=3307`, `APP_LOCALE=th`; README มีขั้นตอนติดตั้ง
  - Verify: `sail artisan test`, เปิดเว็บด้วยมือ
  - Files: ไฟล์ที่ installer สร้าง, `.gitattributes`, `.env.example`, `README.md`

- [ ] **T2 — users schema + UserRole enum**
  - Acceptance: migration เพิ่ม `phone` varchar(20) null, `address` text null, `role` enum default `member`; `User` cast `role` เป็น `UserRole`, มี `isAdmin()`; `role` ไม่อยู่ใน `$fillable`
  - Verify: `tests/Feature/Foundation/UserRoleTest.php`
  - Files: `database/migrations/..._add_profile_and_role_to_users_table.php`, `app/Enums/UserRole.php`, `app/Models/User.php`, `database/factories/UserFactory.php` (state `admin()`), test

- [ ] **T3 — Registration ครบฟิลด์ + redirect ตั้งค่าได้**
  - Acceptance: ฟอร์มสมัครมี phone/address (validate: phone ≤20 ตัว, address ≤500 ตัว, ไม่บังคับ); ส่ง `role=admin` แล้วยังเป็น member; หลังสมัคร redirect ไป `config('roastnotes.after_register_route')`; หลัง login ไป `home`
  - Verify: `tests/Feature/Foundation/RegistrationTest.php`
  - Files: `config/roastnotes.php`, `app/Http/Controllers/Auth/RegisteredUserController.php`, `resources/views/auth/register.blade.php`, `AuthenticatedSessionController.php`, test

- [ ] **T4 — Admin area**
  - Acceptance: middleware alias `admin`; `routes/admin.php` ลงทะเบียน prefix `/admin`; `GET /admin` → admin 200, member 403, guest redirect `/login`
  - Verify: `tests/Feature/Foundation/AdminAccessTest.php`
  - Files: `app/Http/Middleware/EnsureUserIsAdmin.php`, `bootstrap/app.php`, `routes/admin.php`, `resources/views/admin/index.blade.php`, test

- [ ] **T5 — Layout + navigation + หน้า placeholder**
  - Acceptance: layout กลางมีเมนู Home / Products (ทุกคน), ตะกร้า / ข้อมูลสมาชิก / ออกจากระบบ (member), Admin (admin), Login / Register (guest); `/` และ `/products` เปิดได้ไม่ต้องล็อกอิน; `/cart` และ `/member` ต้องล็อกอิน; ลบ dashboard ของ Breeze; หน้า profile ย้ายไป `/member` และแก้ phone/address ได้
  - Verify: `tests/Feature/Foundation/NavigationAccessTest.php` + เทส profile ของ Breeze ที่ปรับ URL
  - Files: `routes/web.php`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/navigation.blade.php`, `resources/views/home.blade.php` (+ placeholder products/cart), test

- [ ] **T6 — Seeders**
  - Acceptance: `migrate:fresh --seed` สร้าง `admin@roastnotes.test` (admin) และ `member1..3@roastnotes.test` (member) รหัส `password`; `DatabaseSeeder` เรียก seeder ต่อโมดูล
  - Verify: `tests/Feature/Foundation/SeederTest.php`
  - Files: `database/seeders/DatabaseSeeder.php`, `database/seeders/UserSeeder.php`, test

- [ ] **T7 — Checkpoint**
  - Acceptance: Success Criteria 1–7 ใน SPEC-foundation ผ่านครบ, `sail pint --test` สะอาด, อัปเดตสถานะใน CAPABILITY-MAP
  - Verify: ไล่ทีละข้อด้วยมือ + `sail artisan test`
  - Files: `docs/specs/CAPABILITY-MAP.md`
