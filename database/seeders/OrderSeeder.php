<?php

namespace Database\Seeders;

use App\Enums\Grind;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Enums\PromotionType;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Random\Engine\Mt19937;
use Random\Randomizer;

/**
 * DEMO DATA: ~60 past orders over the last 90 days for member1–3, so best sellers,
 * sales reports and loyalty tiers have something to show. Deterministic (fixed seed).
 * Stock is not touched — these orders are history, not live sales.
 */
class OrderSeeder extends Seeder
{
    public const ORDERS = 60;

    public const DAYS = 90;

    /** member email => share of orders (member1 buys most, for loyalty tiers later) */
    private const BUYERS = ['member1@roastnotes.test' => 50, 'member2@roastnotes.test' => 30, 'member3@roastnotes.test' => 20];

    private Randomizer $random;

    /** extra demo customers (UserSeeder::COMMUNITY): orders each, so reviews and reports have more voices */
    public const COMMUNITY_ORDERS = [3, 8];

    /** @var array<string, int> running order number per day */
    private array $perDay = [];

    public function run(): void
    {
        $this->random = new Randomizer(new Mt19937(20261021));
        $buyers = User::whereIn('email', array_keys(self::BUYERS))->get()->keyBy('email');
        $variants = ProductVariant::with('product')->whereHas('product', fn ($q) => $q->where('is_active', true))->orderBy('id')->get();
        if ($buyers->isEmpty() || $variants->isEmpty()) {
            return;
        }

        $dates = collect(range(1, self::ORDERS))
            ->map(fn () => $this->randomDate())
            ->sort()->values();

        foreach ($dates as $placedAt) {
            $user = $buyers[$this->weighted(self::BUYERS)] ?? $buyers->first();
            $this->placeOrder($user, $placedAt, $variants, maxLines: 3, dripQuantities: [1, 2, 5, 10], maxBeanBags: 3);
        }

        // Community customers: smaller baskets, own random stream so the 60 orders above never change
        $this->random = new Randomizer(new Mt19937(20261023));
        $community = User::whereIn('email', array_column(UserSeeder::COMMUNITY, 'email'))->orderBy('id')->get();
        $orders = $community->flatMap(fn (User $user) => collect(range(1, $this->random->getInt(...self::COMMUNITY_ORDERS)))
            ->map(fn () => [$user, $this->randomDate()]))
            ->sortBy(fn ($pair) => $pair[1]->getTimestamp())->values();
        foreach ($orders as [$user, $placedAt]) {
            $this->placeOrder($user, $placedAt, $variants, maxLines: 2, dripQuantities: [1, 2, 5, 10], maxBeanBags: 1);
        }

        $this->promotionsUsed($community->pluck('id')->all());
        $this->histories();
    }

    /**
     * Community customers sometimes used a promotion that was running when they ordered: a coupon (within its
     * dates, min spend and per-member limit) or "buy 10 get 2" on drip bags. Own random stream; member1–3 untouched.
     *
     * @param  list<int>  $customerIds
     */
    private function promotionsUsed(array $customerIds): void
    {
        $random = new Randomizer(new Mt19937(20261027));
        $coupons = Promotion::with(['coupon', 'products:id'])->where('type', PromotionType::Coupon)->orderBy('id')->get();
        $deal = Promotion::with(['buyXGetY', 'products:id'])->where('type', PromotionType::BuyXGetY)->first();
        $used = []; // promotion_id => [user_id => times]

        Order::whereIn('user_id', $customerIds)->with('items.variant.product')->orderBy('id')->each(function (Order $order) use ($random, $coupons, $deal, &$used) {
            $at = $order->placed_at;
            $discounts = [];
            $freeOnLine = [];

            // buy 10 get 2: a customer who took 10 sachets of a participating drip bag often grabbed the 2 free ones
            if ($deal && $this->running($deal, $at)) {
                foreach ($order->items as $item) {
                    if ($item->quantity === $deal->buyXGetY->buy_quantity && $deal->products->contains('id', $item->variant->product_id) && $random->getInt(1, 100) <= 60) {
                        $unit = Money::toSatang($item->unit_price);
                        $item->update(['quantity' => $deal->buyXGetY->groupSize(), 'line_total' => Money::fromSatang($unit * $deal->buyXGetY->groupSize())]);
                        $freeOnLine[$item->id] = $unit * $deal->buyXGetY->free_quantity;
                        $discounts[] = ['promotion_id' => $deal->id, 'source' => 'buy_x_get_y', 'label' => $deal->name, 'amount' => $freeOnLine[$item->id]];
                    }
                }
            }

            // one coupon at most
            foreach ($coupons as $promotion) {
                $coupon = $promotion->coupon;
                $eligible = $order->items->filter(fn ($item) => $promotion->products->contains('id', $item->variant->product_id));
                $base = $eligible->sum(fn ($item) => Money::toSatang($item->line_total) - ($freeOnLine[$item->id] ?? 0));
                $times = $used[$promotion->id][$order->user_id] ?? 0;
                if (! $this->running($promotion, $at) || $base <= 0
                    || ($coupon->min_subtotal && $base < Money::toSatang($coupon->min_subtotal))
                    || ($coupon->per_user_limit && $times >= $coupon->per_user_limit)
                    || $random->getInt(1, 100) > 45) {
                    continue;
                }
                $used[$promotion->id][$order->user_id] = $times + 1;
                $discounts[] = ['promotion_id' => $promotion->id, 'source' => 'coupon', 'label' => __(':code :percent% off', ['code' => $coupon->code, 'percent' => $coupon->percent_off]), 'amount' => intdiv($base * $coupon->percent_off, 100)];
                break;
            }

            if (! $discounts) {
                return;
            }
            $subtotal = $order->items->sum(fn ($item) => Money::toSatang($item->line_total));
            $discount = array_sum(array_column($discounts, 'amount'));
            $shipping = $subtotal - $discount >= Money::toSatang(config('roastnotes.free_shipping_threshold')) ? 0 : Money::toSatang(config('roastnotes.shipping_fee'));
            $order->update([
                'subtotal' => Money::fromSatang($subtotal),
                'discount_total' => Money::fromSatang($discount),
                'shipping_fee' => Money::fromSatang($shipping),
                'total' => Money::fromSatang($subtotal - $discount + $shipping),
            ]);
            $order->discounts()->createMany(array_map(fn ($d) => ['amount' => Money::fromSatang($d['amount'])] + $d, $discounts));
        });
    }

    private function running(Promotion $promotion, Carbon $at): bool
    {
        return $promotion->starts_at->lte($at) && ($promotion->ends_at === null || $promotion->ends_at->gte($at));
    }

    /**
     * Status history for every demo order: one step at a time up to its current status, 2–30 hours apart.
     * The customer places (and cancels); the shop admin moves it forward. Own random stream.
     */
    private function histories(): void
    {
        $random = new Randomizer(new Mt19937(20261026));
        $admin = User::where('role', 'admin')->orderBy('id')->first();

        Order::orderBy('id')->each(function (Order $order) use ($random, $admin) {
            $at = $order->placed_at->copy();
            $rows = [['from_status' => null, 'to_status' => OrderStatus::Pending, 'changed_by' => $order->user_id, 'note' => null, 'created_at' => $at->copy()]];

            if ($order->status === OrderStatus::Cancelled) {
                $rows[] = ['from_status' => OrderStatus::Pending, 'to_status' => OrderStatus::Cancelled, 'changed_by' => $order->user_id, 'note' => 'ลูกค้ายกเลิกเอง', 'created_at' => $order->cancelled_at];
            } else {
                $previous = OrderStatus::Pending;
                foreach (array_slice(OrderStatus::FLOW, 1, array_search($order->status, OrderStatus::FLOW, true)) as $status) {
                    $at = $at->copy()->addHours($random->getInt(2, 30));
                    if ($at->isFuture()) {
                        $at = now()->subMinutes($random->getInt(5, 60));
                    }
                    $note = $status === OrderStatus::Shipped ? sprintf('Kerry Express TH%010d', $random->getInt(1, 9999999999)) : null;
                    $rows[] = ['from_status' => $previous, 'to_status' => $status, 'changed_by' => $admin?->id, 'note' => $note, 'created_at' => $at];
                    $previous = $status;
                }
            }

            $order->statusChanges()->createMany($rows);
        });
    }

    private function randomDate(): Carbon
    {
        $at = today()->subDays($this->random->getInt(0, self::DAYS - 1))->setTime($this->random->getInt(8, 21), $this->random->getInt(0, 59));

        return $at->isFuture() ? now()->subHour()->startOfMinute() : $at; // "later today" has not happened yet
    }

    /** @param list<int> $dripQuantities */
    private function placeOrder(User $user, Carbon $placedAt, $variants, int $maxLines, array $dripQuantities, int $maxBeanBags): void
    {
        $day = $placedAt->format('Ymd');
        $this->perDay[$day] = ($this->perDay[$day] ?? 0) + 1;

        $lines = [];
        foreach ($this->pick($variants, $this->random->getInt(1, $maxLines)) as $variant) {
            $isDrip = $variant->product->type === ProductType::DripBag;
            $quantity = $isDrip ? $dripQuantities[$this->random->getInt(0, count($dripQuantities) - 1)] : $this->random->getInt(1, $maxBeanBags);
            $unit = Money::toSatang($variant->price);
            $lines[] = [
                'product_variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'size' => $variant->size->value,
                'grind' => $isDrip ? Grind::None : Grind::forBeans()[$this->random->getInt(0, 2)],
                'unit_price' => Money::fromSatang($unit),
                'quantity' => $quantity,
                'line_total' => Money::fromSatang($unit * $quantity),
            ];
        }

        $subtotal = array_sum(array_map(fn ($l) => Money::toSatang($l['line_total']), $lines));
        $shipping = $subtotal >= Money::toSatang(config('roastnotes.free_shipping_threshold')) ? 0 : Money::toSatang(config('roastnotes.shipping_fee'));
        $status = $this->status($placedAt);

        $order = Order::create([
            'order_number' => sprintf('RN-%s-%04d', $day, $this->perDay[$day]),
            'user_id' => $user->id,
            'status' => $status,
            'subtotal' => Money::fromSatang($subtotal),
            'discount_total' => '0.00',
            'shipping_fee' => Money::fromSatang($shipping),
            'total' => Money::fromSatang($subtotal + $shipping),
            'recipient_name' => $user->name,
            'phone' => $user->phone ?? '0800000000',
            'shipping_address' => $user->address ?? '-',
            'placed_at' => $placedAt,
            'cancelled_at' => $status === OrderStatus::Cancelled ? $placedAt->copy()->addHours(2) : null,
            'created_at' => $placedAt,
            'updated_at' => $placedAt,
        ]);
        $order->items()->createMany($lines);
    }

    /** Older than a week: delivered (~5% cancelled). Recent: somewhere along the flow. */
    private function status(Carbon $placedAt): OrderStatus
    {
        if ($this->random->getInt(1, 100) <= 5) {
            return OrderStatus::Cancelled;
        }

        return $placedAt->lt(today()->subDays(7))
            ? OrderStatus::Delivered
            : [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::InTransit, OrderStatus::Delivered][$this->random->getInt(0, 4)];
    }

    private function pick($variants, int $count): array
    {
        $keys = $this->random->pickArrayKeys($variants->all(), min($count, $variants->count()));

        return array_map(fn ($key) => $variants[$key], $keys);
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
