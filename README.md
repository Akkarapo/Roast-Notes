# Roast & Notes ☕

ร้านเมล็ดกาแฟ Specialty และดริปแบ็ก — Term Project วิชา Database (261343)

- Spec และแผนงาน: [`docs/specs/`](docs/specs/) · [`tasks/`](tasks/)
- Stack: Laravel 12 · PHP 8.3 · MySQL 8 · Laravel Sail · Breeze (Blade + Tailwind)

## ติดตั้งครั้งแรก (Windows)

ต้องมี: Windows 10/11 · RAM 8 GB ขึ้นไป · พื้นที่ว่าง ~10 GB · สิทธิ์เข้า repo นี้ (ถ้า repo เป็น private ให้เจ้าของเพิ่มเป็น collaborator)

**1. ติดตั้ง WSL2 + Ubuntu** — เปิด PowerShell แบบ *Run as administrator*

```powershell
wsl --install -d Ubuntu
```

รีสตาร์ทเครื่อง แล้วเปิดแอป **Ubuntu** ตั้ง username / password ให้เสร็จ

**2. ติดตั้ง Docker Desktop** — https://www.docker.com/products/docker-desktop/
เปิด Settings → Resources → WSL integration → เปิดสวิตช์ **Ubuntu** → Apply & restart · เปิด Docker Desktop ค้างไว้ทุกครั้งที่ใช้งาน

**3. ดึงโปรเจคลงใน Ubuntu** (เก็บใน `~` ของ Ubuntu จะเร็วกว่าเก็บใน `/mnt/c` หรือ `/mnt/d` มาก)

```bash
cd ~
git clone https://github.com/Akkarapo/Roast-Notes.git roast-notes
cd roast-notes
```

**4. ติดตั้งและเปิดเว็บ** (ครั้งแรกใช้เวลา 5–15 นาที เพราะต้องโหลดและ build image)

```bash
# ติดตั้ง PHP dependencies ผ่าน Docker (ไม่ต้องมี PHP/Composer ในเครื่อง)
docker run --rm -u "$(id -u):$(id -g)" -v "$(pwd):/var/www/html" -w /var/www/html \
    laravelsail/php83-composer:latest composer install --ignore-platform-reqs

cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate:fresh --seed
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

เปิดเว็บที่ http://localhost:8080 · MySQL จากเครื่อง (DBeaver ฯลฯ) ต่อที่ `127.0.0.1:3307` user `sail` / `password`

**บัญชีทดสอบ** (รหัสผ่าน `password` ทุกบัญชี): `admin@roastnotes.test` · `member1@roastnotes.test` (Roaster Master) · `member2@roastnotes.test` (Barista) · `member3@roastnotes.test` (Bean)

### ครั้งต่อไป

```bash
cd ~/roast-notes
./vendor/bin/sail up -d          # เปิด
./vendor/bin/sail stop           # ปิด
git pull && ./vendor/bin/sail npm run build                       # อัปเดตโค้ด
./vendor/bin/sail artisan migrate:fresh --seed                    # ล้างข้อมูลกลับเป็นค่าตัวอย่าง
```

### ปัญหาที่เจอบ่อย

| อาการ | แก้ |
|---|---|
| `Cannot connect to the Docker daemon` | เปิด Docker Desktop ก่อน และเช็กว่าเปิด WSL integration ของ Ubuntu แล้ว |
| port 8080 หรือ 3307 ถูกใช้อยู่ | แก้ `APP_PORT` / `FORWARD_DB_PORT` ใน `.env` แล้ว `sail up -d` ใหม่ |
| เว็บขึ้นแต่ไม่มีสี/หน้าตาเพี้ยน | `./vendor/bin/sail npm run build` |
| `No application encryption key` | `./vendor/bin/sail artisan key:generate` |
| `Connection refused` ตอน migrate | รอ MySQL บูตสัก 10–20 วินาทีแล้วรันใหม่ |
| ช้ามาก | ย้ายโปรเจคมาไว้ใน `~` ของ Ubuntu (ไม่ใช่ `/mnt/c/...`) |

> ตั้ง alias ไว้พิมพ์สั้น ๆ: `alias sail='./vendor/bin/sail'`

## คำสั่งระหว่างพัฒนา

```bash
sail up -d                       # เปิด container
sail npm run dev                 # Vite hot reload
sail artisan test                # รันเทสทั้งหมด
sail artisan test --filter=Foundation
sail pint                        # จัด format โค้ด
sail artisan migrate:fresh --seed  # ล้างฐานข้อมูลแล้วใส่ข้อมูลตัวอย่างใหม่
sail down                        # ปิด container
```

## บัญชีตัวอย่าง (หลัง `--seed`)

| Role | Email | Password |
|---|---|---|
| admin | admin@roastnotes.test | password |
| member | member1@roastnotes.test – member3@roastnotes.test | password |
