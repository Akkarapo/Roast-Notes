# Roast & Notes ☕

ร้านเมล็ดกาแฟ Specialty และดริปแบ็ก — Term Project วิชา Database (261343)

- Spec และแผนงาน: [`docs/specs/`](docs/specs/) · [`tasks/`](tasks/)
- Stack: Laravel 12 · PHP 8.3 · MySQL 8 · Laravel Sail · Breeze (Blade + Tailwind)

## ติดตั้งครั้งแรก (Windows + WSL2 + Docker Desktop)

ต้องเปิด Docker Desktop ไว้ก่อน แล้วรันทุกคำสั่งใน **WSL (Ubuntu)**

```bash
cd "/mnt/d/homwork/Project DataBase/roast-notes"

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
