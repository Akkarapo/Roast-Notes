# Spec: foundation

> โมดูลแรกของ Roast & Notes — ดู [CAPABILITY-MAP.md](CAPABILITY-MAP.md)
> สถานะ: **อนุมัติแล้ว (2026-10-21)** · Depends on: — · ผู้ใช้ต่อ: ทุกโมดูล

## Objective

วางโครงโปรเจค Laravel ที่รันได้ พร้อมระบบสมัครสมาชิก/ล็อกอิน แยกสิทธิ์ member กับ admin และ layout กลางที่ทุกหน้าใช้ร่วมกัน เพื่อให้โมดูลถัดไปเพิ่มหน้าและตารางได้ทันทีโดยไม่ต้องแตะโครงสร้างพื้นฐานอีก

ครอบคลุม Core Features ของโจทย์: **Register Page**, **Log-in Page** และ Home Page แบบโครง (เนื้อหาเติมใน `catalog`, `ordering`, `taste-profile`)

**User stories**
- ในฐานะผู้เยี่ยมชม ฉันสมัครสมาชิกด้วยชื่อ อีเมล รหัสผ่าน เบอร์โทร และที่อยู่ได้ แล้วได้ role `member` อัตโนมัติ
- ในฐานะสมาชิก ฉันล็อกอิน/ล็อกเอาต์ได้ และเห็นเมนูของสมาชิก (ตะกร้า, ข้อมูลสมาชิก)
- ในฐานะแอดมิน ฉันล็อกอินแล้วเข้า `/admin` ได้ ส่วนสมาชิกทั่วไปเข้าไม่ได้ (403)
- ในฐานะผู้เยี่ยมชม ถ้าเข้าหน้าที่ต้องล็อกอิน (เช่น `/cart`) ระบบพาไปหน้า Login

**นอกขอบเขต** (อยู่ในโมดูลอื่น): Taste Quiz หลังสมัคร (`taste-profile`), ระดับสมาชิก (`loyalty`), หน้า Member Info เต็มรูปแบบพร้อมประวัติสั่งซื้อ (`ordering`), จัดการผู้ใช้ใน dashboard (`admin`)

## Tech Stack

| ส่วน | เลือกใช้ |
|---|---|
| Framework | Laravel 12, PHP 8.3 |
| Dev environment | Laravel Sail (Docker, บน Windows ใช้ผ่าน WSL2) |
| Database | MySQL 8 (service `mysql` ของ Sail) |
| Auth scaffolding | Laravel Breeze — Blade stack |
| Frontend | Blade + Tailwind CSS + Alpine.js (มากับ Breeze), Vite |
| Test | PHPUnit (ค่าเริ่มต้นของ Laravel) |
| Format | Laravel Pint |

## Commands

```bash
# ครั้งแรก (ใน WSL2)
git clone https://github.com/Akkarapo/Roast-Notes.git roast-notes && cd roast-notes
composer install                      # หรือใช้ docker run laravelsail/php83-composer ถ้าเครื่องไม่มี composer
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install

# ระหว่างพัฒนา
./vendor/bin/sail up -d               # เปิดเว็บที่ http://localhost
./vendor/bin/sail npm run dev         # Vite hot reload
./vendor/bin/sail artisan test        # รันเทสทั้งหมด
./vendor/bin/sail artisan test --filter=Auth
./vendor/bin/sail pint                # จัด format
./vendor/bin/sail npm run build       # build asset ก่อน demo
```

## Project Structure

repo `roast-notes/` = ราก Laravel ตามมาตรฐาน + โฟลเดอร์เอกสาร

```
app/Http/Controllers/          → controller แยกตามโมดูล เช่น Admin/, Shop/
app/Http/Middleware/           → EnsureUserIsAdmin
app/Models/                    → Eloquent models
app/Enums/                     → UserRole (และ enum ของโมดูลอื่นในภายหลัง)
database/migrations/           → migration ทุกตาราง (ชื่อไฟล์บอกโมดูลผ่านชื่อตาราง)
database/seeders/              → DatabaseSeeder เรียก seeder ต่อโมดูล
resources/views/layouts/       → app.blade.php (layout กลาง), guest.blade.php
resources/views/components/    → Blade components ใช้ซ้ำ
resources/views/<module>/      → view ของแต่ละโมดูล
routes/web.php                 → route สาธารณะ + member
routes/admin.php               → route /admin/* (prefix + middleware admin)
tests/Feature/<Module>/        → feature tests ต่อโมดูล
docs/specs/                    → CAPABILITY-MAP.md, SPEC-<module>.md
docs/design/                   → ER/EER diagram, relational mapping, low-fi prototype
tasks/                         → plan.md, todo.md
```

## Data (โมดูลนี้เป็นเจ้าของ)

**users** — ขยายจากตาราง users ของ Laravel

| คอลัมน์ | ชนิด | หมายเหตุ |
|---|---|---|
| id | bigint PK | |
| name | varchar(100) | |
| email | varchar(255) UNIQUE | |
| password | varchar(255) | hash |
| phone | varchar(20) NULL | |
| address | text NULL | ที่อยู่จัดส่งตั้งต้น |
| role | enum('member','admin') default 'member' | |
| email_verified_at, remember_token, timestamps | | มาตรฐาน Laravel |

คอลัมน์ที่โมดูลอื่นเพิ่มเองผ่าน migration ของตัวเอง: `preferred_roast_level` (`taste-profile`), `member_tier_id` (`loyalty`)

**Seed**: admin 1 คน (`admin@roastnotes.test` / `password`), member ตัวอย่าง 3 คน (`member1..3@roastnotes.test` / `password`)

## Code Style

ทำตาม Laravel conventions + Pint preset `laravel` ชื่อตาราง/คอลัมน์ snake_case พหูพจน์, ข้อความบน UI ภาษาไทย

```php
// app/Enums/UserRole.php
enum UserRole: string
{
    case Member = 'member';
    case Admin = 'admin';
}

// app/Models/User.php
protected function casts(): array
{
    return [
        'password' => 'hashed',
        'role' => UserRole::class,
    ];
}

public function isAdmin(): bool
{
    return $this->role === UserRole::Admin;
}

// app/Http/Middleware/EnsureUserIsAdmin.php
public function handle(Request $request, Closure $next): Response
{
    abort_unless($request->user()?->isAdmin(), 403);

    return $next($request);
}
```

- Validation ใช้ Form Request (`app/Http/Requests/...`) ไม่ validate ใน controller
- ห้าม mass-assign `role` จากฟอร์มสมัคร (ไม่อยู่ใน `$fillable` ของ register flow)
- Controller บาง: logic ธุรกิจที่ใช้ซ้ำย้ายไป `app/Services/` (เริ่มใช้ตั้งแต่ `ordering`)

## Testing Strategy

- Feature tests ด้วย PHPUnit + `RefreshDatabase` บนฐาน `testing` ของ Sail (MySQL ตัวเดียวกับ dev เพื่อให้ query เฉพาะ MySQL ในโมดูลรายงานเทสได้)
- เก็บที่ `tests/Feature/Foundation/` และคงเทสของ Breeze ไว้ (`tests/Feature/Auth/`)
- เทสที่ต้องมีในโมดูลนี้:
  - สมัครสมาชิกพร้อม phone/address แล้ว role = member
  - ส่ง `role=admin` มาในฟอร์มสมัครแล้วยังได้ member
  - member เข้า `/admin` ได้ 403, admin ได้ 200, guest ถูก redirect ไป `/login`
  - หน้า `/` และ `/products` เปิดได้โดยไม่ล็อกอิน
- เกณฑ์: `sail artisan test` ผ่านทั้งหมดก่อน commit ทุกครั้ง

## Boundaries

- **Always:** รันเทสก่อน commit · ใช้ Form Request validate input · ใช้ migration ทุกครั้งที่แก้ schema · อัปเดต spec ก่อนถ้าการตัดสินใจเปลี่ยน
- **Ask first:** เพิ่ม composer/npm package นอกเหนือจาก Breeze · เปลี่ยนชื่อ/ลบคอลัมน์ที่โมดูลอื่นใช้แล้ว · เปลี่ยน stack (เช่นไป Livewire/Inertia)
- **Never:** commit `.env` หรือรหัสผ่านจริง · แก้ไฟล์ใน `vendor/` · ลบหรือข้ามเทสที่ fail เพื่อให้ผ่าน

## Success Criteria

1. `git clone` → ทำตาม Commands → เปิด `http://localhost` เห็นหน้า Home ที่มีเมนู Home / Products / Login / Register
2. สมัครสมาชิกใหม่ได้ ข้อมูลลงตาราง `users` พร้อม phone, address และ role = `member`
3. ล็อกอินด้วยบัญชี seed ได้ทั้ง admin และ member; เมนูเปลี่ยนตามสถานะ (member เห็น ตะกร้า/ข้อมูลสมาชิก/ออกจากระบบ, admin เห็น Admin เพิ่ม)
4. `/admin` ตอบ 200 กับ admin, 403 กับ member, redirect `/login` กับ guest
5. `/cart` และ `/member` (หน้าว่างชั่วคราว) ต้องล็อกอินก่อน
6. หลังสมัครเสร็จ redirect ไปยัง route ที่ตั้งค่าได้ที่จุดเดียว (ตอนนี้ไป `/`, โมดูล `taste-profile` จะเปลี่ยนเป็น `/taste-quiz`)
7. `sail artisan test` ผ่านทั้งหมด และ `sail pint --test` ไม่มีไฟล์ต้องแก้

## Decisions (ปิด Open Questions — 2026-10-21)

1. Stack ตรงกับแลป: Laravel 12 + PHP 8.3 + Breeze (Blade)
2. โปรเจคอยู่ที่ `D:\homwork\Project DataBase\roast-notes` (clone จาก repo) และ spec อยู่ที่ `docs/specs/`
3. ไม่ต้องยืนยันอีเมล — `User` ไม่ implement `MustVerifyEmail` (ทบทวนได้ภายหลัง)
