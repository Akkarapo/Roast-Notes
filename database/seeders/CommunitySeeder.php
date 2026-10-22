<?php

namespace Database\Seeders;

use App\Enums\BrewMethod;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * DEMO DATA (after OrderSeeder + LoyaltySeeder): reviews for ~80% of delivered order lines and a few
 * Wish List items per member. Deterministic (fixed seed). See docs/specs/SPEC-community.md.
 */
class CommunitySeeder extends Seeder
{
    public const REVIEW_SHARE = 80; // percent of delivered lines

    /** brew method weights by roast level (light → filter, dark → espresso / moka) */
    private const BREW_WEIGHTS = [
        'light' => ['pour_over' => 45, 'aeropress' => 25, 'cold_brew' => 10, 'french_press' => 10, 'espresso' => 10],
        'medium' => ['pour_over' => 25, 'espresso' => 25, 'aeropress' => 15, 'french_press' => 15, 'moka_pot' => 10, 'cold_brew' => 10],
        'dark' => ['espresso' => 40, 'moka_pot' => 25, 'french_press' => 20, 'cold_brew' => 15],
    ];

    private const OPENERS = [
        'pour_over' => ['ดริปมือ V60 แล้ว', 'ชงดริปตอนเช้า', 'ดริป 1:15 น้ำ 92 องศา'],
        'aeropress' => ['ชง AeroPress แบบกลับหัว', 'AeroPress 2 นาที'],
        'cold_brew' => ['ทำโคลด์บรูว์ทิ้งไว้ข้ามคืน', 'สกัดเย็น 16 ชั่วโมง'],
        'french_press' => ['ชงเฟรนช์เพรส 4 นาที', 'แช่เฟรนช์เพรสแล้วกด'],
        'espresso' => ['ดึงช็อตเอสเปรสโซ', 'ชงเอสเปรสโซ 1:2', 'ทำลาเต้กับเอสเปรสโซ'],
        'moka_pot' => ['ต้มหม้อโมก้าบนเตา', 'ใช้หม้อโมก้าทำกาแฟเช้า'],
        'drip_bag' => ['ฉีกซองดริปที่ออฟฟิศ', 'พกดริปแบ็กไปเที่ยว', 'ดริปแบ็กตอนบ่าย'],
    ];

    private const VERDICTS = [
        5 => ['ชอบมาก จะสั่งอีกแน่นอน', 'หอมทั้งห้อง ดีเกินราคา', 'เป็นถุงโปรดของเดือนนี้'],
        4 => ['อร่อย สมดุลดี', 'ดีเลย หวานยาว', 'ดื่มง่าย ไม่ผิดหวัง'],
        3 => ['พอใช้ได้ แต่ยังไม่ใช่สไตล์เรา', 'กลาง ๆ ต้องลองปรับสูตรอีก', 'โอเค แต่เปรี้ยวไปหน่อยสำหรับเรา'],
        2 => ['ไม่ค่อยเข้ากับวิธีชงนี้', 'ขมไปหน่อย'],
        1 => ['ไม่ใช่ทางเราเลย'],
    ];

    private Randomizer $random;

    public function run(): void
    {
        $this->random = new Randomizer(new Mt19937(20261025));

        $items = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('status', OrderStatus::Delivered))
            ->with(['order.user', 'variant.product.flavorNotes', 'variant.product.cupping'])
            ->orderBy('id')
            ->get();

        foreach ($items as $item) {
            if ($this->random->getInt(1, 100) > self::REVIEW_SHARE) {
                continue;
            }
            $product = $item->variant->product;
            $brew = $this->brewFor($product, $item->order->user);
            $rating = $this->ratingFor($product, $brew);

            $review = new Review([
                'rating' => $rating,
                'brew_method' => $brew,
                'comment' => $this->random->getInt(1, 100) <= 20 ? null : $this->comment($product, $brew, $rating),
            ]);
            $review->order_item_id = $item->id;
            $review->created_at = $review->updated_at = $this->writtenAt($item->order->placed_at);
            $review->save();
        }

        $this->wishlists();
    }

    private function brewFor(Product $product, User $user): BrewMethod
    {
        if ($product->type === ProductType::DripBag) {
            return BrewMethod::DripBag;
        }
        // members often brew the way their Taste Profile says
        if ($user->preferred_brew_method && $user->preferred_brew_method !== BrewMethod::DripBag && $this->random->getInt(1, 100) <= 40) {
            return $user->preferred_brew_method;
        }

        return BrewMethod::from($this->weighted(self::BREW_WEIGHTS[($product->roast_level ?? RoastLevel::Medium)->value]));
    }

    /** Higher cupping score → more stars; light roasts pulled as espresso and dark roasts as cold drip lose a star sometimes. */
    private function ratingFor(Product $product, BrewMethod $brew): int
    {
        $score = (float) ($product->cupping?->total_points ?? 84);
        $base = match (true) {
            $score >= 87 => 5,
            $score >= 85 => $this->random->getInt(4, 5),
            $score >= 83 => 4,
            default => $this->random->getInt(3, 4),
        };
        $mismatch = ($product->roast_level === RoastLevel::Light && in_array($brew, [BrewMethod::Espresso, BrewMethod::MokaPot], true))
            || ($product->roast_level === RoastLevel::Dark && in_array($brew, [BrewMethod::PourOver, BrewMethod::AeroPress], true));
        if ($mismatch && $this->random->getInt(1, 100) <= 70) {
            $base -= $this->random->getInt(1, 2);
        }
        if ($this->random->getInt(1, 100) <= 15) {
            $base += $this->random->getInt(0, 1) ? 1 : -1;
        }

        return max(1, min(5, $base));
    }

    private function comment(Product $product, BrewMethod $brew, int $rating): string
    {
        $notes = $product->flavorNotes->pluck('name')->all();
        $openers = self::OPENERS[$brew->value];
        $opener = $openers[$this->random->getInt(0, count($openers) - 1)];
        $verdict = self::VERDICTS[$rating][$this->random->getInt(0, count(self::VERDICTS[$rating]) - 1)];

        if (! $notes || $this->random->getInt(1, 100) <= 25) {
            $mouth = ['บอดี้กลาง ๆ จบสะอาด', 'ความเปรี้ยวสดชื่น', 'บอดี้หนา นุ่มลิ้น', 'หวานฉ่ำตอนอุ่นลง', 'จบสั้นไปนิด'];

            return $opener.' '.$mouth[$this->random->getInt(0, count($mouth) - 1)].' '.$verdict;
        }

        $first = $notes[$this->random->getInt(0, count($notes) - 1)];
        $others = array_values(array_diff($notes, [$first]));
        $second = $others ? $others[$this->random->getInt(0, count($others) - 1)] : null;
        $templates = array_filter([
            "ได้กลิ่น {$first} ชัด",
            "โน้ต {$first} มาก่อนเลย",
            $second ? "เปิดมาด้วย {$first} ท้ายคอเป็น {$second}" : null,
            $second ? "{$first} กับ {$second} ตามที่ร้านเขียนไว้จริง" : null,
            "พออุ่นลงได้ {$first} ชัดขึ้น",
        ]);
        $templates = array_values($templates);

        return $opener.' '.$templates[$this->random->getInt(0, count($templates) - 1)].' '.$verdict;
    }

    private function writtenAt(Carbon $placedAt): Carbon
    {
        $at = $placedAt->copy()->addDays($this->random->getInt(4, 12))->setTime($this->random->getInt(7, 22), $this->random->getInt(0, 59));

        return $at->isFuture() ? now()->subHour() : $at;
    }

    /** 3–5 saved products per member, one of them a rare lot when there is one. */
    private function wishlists(): void
    {
        $productIds = Product::where('is_active', true)->orderBy('id')->pluck('id')->all();
        $rare = Product::exclusiveNow()->orderBy('id')->pluck('id')->all();

        User::where('role', 'member')->orderBy('id')->each(function (User $user) use ($productIds, $rare) {
            $keys = $this->random->pickArrayKeys($productIds, $this->random->getInt(3, 5));
            $ids = array_map(fn ($k) => $productIds[$k], $keys);
            if ($rare) {
                $ids[] = $rare[$this->random->getInt(0, count($rare) - 1)];
            }
            $user->wishlist()->syncWithoutDetaching(array_values(array_unique($ids)));
        });
    }

    /** @param array<string, int> $weights */
    private function weighted(array $weights): string
    {
        $roll = $this->random->getInt(1, array_sum($weights));
        foreach ($weights as $key => $weight) {
            if (($roll -= $weight) <= 0) {
                return $key;
            }
        }

        return array_key_first($weights);
    }
}
