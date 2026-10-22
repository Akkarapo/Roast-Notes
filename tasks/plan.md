# Plan: loyalty

> อ้างอิง [docs/specs/SPEC-loyalty.md](../docs/specs/SPEC-loyalty.md) · สถานะ: **อนุมัติแล้ว**

## ลำดับ

```
L1 Schema: member_tiers (+3 แถวใน migration), users.member_tier_id, products.exclusive_until + models
 ├─ L2 RecalculateMemberTier + LoyaltyStatus (ยอดสะสม, ระดับถัดไป, ยอดที่ขาด)
 │   ├─ L3 LoyaltyTierRule ใน pipeline + PlaceOrder อ่านระดับใน transaction
 │   └─ L4 Member Info การ์ดระดับ + ป้ายใน navbar + ข้อความชวนในตะกร้า
 └─ L5 Early access: Product::isExclusiveNow, ProductPolicy::purchase, AddToCart/PlaceOrder ปฏิเสธ, ป้าย + ปุ่มปิด
     └─ L6 LoyaltySeeder (ระดับ demo + ล็อตหายาก 3 ตัว)
         └─ L7 Checkpoint
```

## การตัดสินใจทางเทคนิค

| เรื่อง | เลือก | เหตุผล |
|---|---|---|
| ข้อมูลระดับ | insert 3 แถวใน migration, ใช้ `slug` อ้างอิงในโค้ด | ระบบต้องมีเสมอ เทสไม่ต้อง seed เพิ่ม |
| default ของ users.member_tier_id | migration ตั้ง default = id ของ Bean; `User::creating` เติมให้ถ้าว่าง | สมาชิกใหม่เป็น Bean เสมอ |
| ยอดสะสม | `orders.where(status, delivered)->sum(total)` ทุกครั้ง | ไม่มีข้อมูลซ้ำซ้อน, ข้อมูลเล็ก |
| เรียก RecalculateMemberTier | method `Order::markDelivered()` ที่ admin จะใช้ + LoyaltySeeder | จุดเดียวที่สถานะกลายเป็น delivered |
| ไม่ลดระดับ | เลือก `max(ระดับเดิม, ระดับที่คำนวณได้)` ตาม min_spend | ตามที่ตกลง |
| early access | `ProductPolicy::purchase` ใช้ทั้ง AddToCartRequest::authorize, หน้าสินค้า (`@can`), PlaceOrder | กติกาเดียวทุกที่ |
| PlaceOrder ปฏิเสธล็อตหายาก | throw ValidationException(['cart' => ...]) แบบเดียวกับสต็อกไม่พอ | ใช้ flow กลับตะกร้าเดิม |

## ความเสี่ยง

| ความเสี่ยง | วิธีลด |
|---|---|
| เพิ่มกฎตัวที่ 3 แล้วเทส ordering/promotions เดิมเปลี่ยนตัวเลข | factory ผู้ใช้เป็น Bean (0%) โดย default |
| ยอดสะสม member ใน demo ขึ้นกับวันที่ seed | OrderSeeder ใช้ seed คงที่ ตรวจใน L6 ว่าได้ 3 ระดับตามที่คาด |
| หน้าเว็บ query ยอดสะสมซ้ำหลายจุด | `LoyaltyStatus` cache ต่อ request แบบเดียวกับ PromotionBadges |

## Checkpoints
- หลัง L3: คิดมือ 2 กรณี (Barista + คูปอง, Roaster Master + ซื้อ 10 แถม 2) เทียบกับหน้าจอ
- L7: Success Criteria 1–5 + screenshot
