# Design Spec: Roast & Notes

> สถานะ: **อนุมัติแล้ว (2026-10-21)** · ใช้กับทุกโมดูลที่มีหน้าจอ
> Reference จากผู้ใช้: two-tone cobalt + cream, ภาพวาดลายเส้น, ตัวหนังสือลายมือเป็นจุดเน้น (ใช้เป็นแรงบันดาลใจด้านสไตล์เท่านั้น — ภาพประกอบทุกชิ้นวาดใหม่เอง)
> Mock: `docs/design/mock-home-desktop.png`, `docs/design/mock-home-mobile.png` · หน้าจริง: `docs/design/screen-*.png`

## หลักการ

1. **สองสีเท่านั้น** — น้ำเงินโคบอลต์บนพื้นครีม ไม่มีสีที่สาม (สถานะ error/success ใช้สีโคบอลต์ + ไอคอน/ข้อความ ยกเว้น error ใช้แดงอิฐได้เฉพาะข้อความเตือน)
2. **ลายเส้นแทนรูปถ่าย** — สินค้า, hero, empty state ใช้ภาพวาด SVG เส้นเดียว stroke เท่ากัน
3. **ลายมือ = จุดเน้น** — ชื่อเล่นสินค้า, ราคา, match % และ tagline ใช้ฟอนต์ลายมือ; ข้อมูลที่ต้องอ่านจริงใช้ฟอนต์ sans
4. **ขอบเส้นชัด มุมมน** — การ์ด/ปุ่ม/ช่องกรอกมีเส้นขอบ 2px สีโคบอลต์ ไม่ใช้เงา

## Tokens

| Token | ค่า | ใช้กับ |
|---|---|---|
| `cobalt` | `#2338C4` | สีหลัก ตัวหนังสือ เส้นขอบ พื้นการ์ดเด่น |
| `cobalt-ink` | `#18248A` | hover/active ของปุ่มทึบ |
| `cream` | `#F3EEE3` | พื้นหลังหน้า |
| `paper` | `#FBF8F1` | พื้นการ์ด ช่องกรอก ตัวหนังสือบนพื้นโคบอลต์ |
| `brick` | `#B3261E` | ข้อความ error เท่านั้น |
| radius | การ์ด 22px · hero 28px · ปุ่ม/chip เต็ม (pill) | |
| border | 2px (การ์ด, ปุ่ม, nav) · 1.5px (chip, เส้นประในการ์ด) | |

## ตัวอักษร

| บทบาท | ฟอนต์ | หมายเหตุ |
|---|---|---|
| Sans (หัวข้อ + เนื้อหา) | **Anuphan** 400/500/600/700 | รองรับไทย+อังกฤษ |
| Script (จุดเน้น) | **Sriracha** | ลายมือ รองรับไทย+อังกฤษ |

โหลดผ่าน Bunny Fonts (ตัวเดียวกับที่ Breeze ใช้ ไม่ต้องเพิ่ม package)

## Components หลัก

| Component | ลักษณะ |
|---|---|
| Navbar | โลโก้เมล็ดลายเส้น + "Roast & Notes" (& เป็นลายมือ), เมนูกลาง, ขีดใต้ 3px ที่เมนูปัจจุบัน, เส้นคั่นล่าง 2px |
| Button | pill · `solid` (พื้นโคบอลต์) · `ghost` (ขอบโคบอลต์) · `cream`/`light` สำหรับวางบนพื้นโคบอลต์ |
| Chip | ตัวกรองโน้ต/ระดับคั่ว/ประเภท · ขอบ 1.5px · เลือกแล้ว = พื้นโคบอลต์ |
| Product card | พื้น paper ขอบ 2px · แหล่งปลูก/process เล็ก · ชื่อ sans + ชื่อเล่น script · ภาพวาดถุง/ดริปแบ็ก · โน้ตรส · เส้นประ · ราคาเป็นป้ายลายมือเอียง · ปุ่ม "ใส่ตะกร้า ›" · hover/แนะนำ = กลับสีเป็นพื้นโคบอลต์ |
| Match badge | วงกลมขอบเส้น มุมขวาบนการ์ด เอียง 8° · ตัวเลขลายมือ "85%" + "เข้ากับลิ้น" · แสดงเฉพาะผู้ที่ล็อกอินและทำ Taste Quiz แล้ว |
| Hero | กล่องโคบอลต์มุม 28px · หัวข้อไทย + tagline ลายมือ · ภาพวาดกาดริป |
| Form | ช่องกรอกพื้น paper ขอบ 2px มุม 14px · label ตัวหนา · focus = ขอบหนาขึ้น (ไม่ใช้ ring สีม่วงของ Breeze) |

## ภาพประกอบ (SVG วาดเอง)

ถุงเมล็ด · ดริปแบ็ก · กาดริป + dripper · เมล็ดกาแฟ (โลโก้) · ตะกร้าว่าง · ถ้วยกาแฟ (empty state) — stroke 3.5–5px, `stroke-linecap: round`, สีตาม `currentColor` เพื่อกลับสีได้

## การนำไปใช้

- เพิ่ม tokens ใน `tailwind.config.js` (`colors.cobalt`, `cream`, `paper`, `fontFamily.sans/script`)
- ปรับ Blade components ของ Breeze (`primary-button`, `text-input`, `nav-link` ฯลฯ) ให้ใช้ tokens — หน้าเดิมทั้งหมดเปลี่ยนตามอัตโนมัติ
- ภาพวาดเก็บเป็น Blade components ที่ `resources/views/components/illustrations/`
- Low-fidelity prototype สำหรับส่งอาจารย์ ทำจาก mock ชุดนี้ (wireframe ขาวดำทุกหน้า) — ทำเมื่อผู้ใช้ขอ

## Mascot: "Bean" (2026-10-23)

ตัวละครที่เจ้าของโปรเจคออกแบบเอง (ภาพต้นฉบับใน `docs/design/mascot/reference-*.png`) วาดใหม่เป็น SVG ลายเส้นใน `<x-mascot pose="…">`

- รูปร่าง: เมล็ดกาแฟสีครีม `#F3EEE3` มีรอยผ่ากลาง, ตาจุด 2 จุด, แขนขาสั้น, เส้นขอบเข้ม `#1B1F3B` 3.5px
- ของประกอบ (ไอน้ำ, ประกาย, หยดน้ำ, zZ) ใช้ `currentColor` → ขาวบนพื้นน้ำเงิน, น้ำเงินบนพื้นครีม
- ท่า: `idle` `wave` `pour` `sip` `cheer` `bag` `search` `sleep` (ดู `docs/design/mascot/poses.png`)

| ที่ใช้ | ท่า |
|---|---|
| Hero หน้าแรก | pour |
| หน้า Login / Register | wave |
| ชวนทำ Taste Quiz (หน้าแรก) | search |
| หน้า Taste Quiz | sip |
| Empty state ทั่วไป / ตะกร้าว่าง | sleep |
| ไม่พบสินค้า / Wish List ว่าง / 404 | search |
| ยังไม่มีออเดอร์ | bag |
| สั่งซื้อสำเร็จ | cheer |
| การ์ดระดับสมาชิก | Bean = wave, Barista = pour, Roaster Master = cheer |

ไอคอนแท็บเบราว์เซอร์ (`public/favicon.svg`, `.ico`, `-32.png`, `apple-touch-icon.png`) = หน้าเมล็ด Bean บนพื้น cobalt
