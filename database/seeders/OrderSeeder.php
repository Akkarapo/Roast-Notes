<?php

namespace Database\Seeders;

use App\Enums\Grind;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Order;
use App\Models\ProductVariant;
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

    public function run(): void
    {
        $this->random = new Randomizer(new Mt19937(20261021));
        $buyers = User::whereIn('email', array_keys(self::BUYERS))->get()->keyBy('email');
        $variants = ProductVariant::with('product')->whereHas('product', fn ($q) => $q->where('is_active', true))->orderBy('id')->get();
        if ($buyers->isEmpty() || $variants->isEmpty()) {
            return;
        }

        $dates = collect(range(1, self::ORDERS))
            ->map(fn () => today()->subDays($this->random->getInt(0, self::DAYS - 1))->setTime($this->random->getInt(8, 21), $this->random->getInt(0, 59)))
            ->sort()->values();
        $perDay = [];

        foreach ($dates as $placedAt) {
            $user = $buyers[$this->weighted(self::BUYERS)] ?? $buyers->first();
            $day = $placedAt->format('Ymd');
            $perDay[$day] = ($perDay[$day] ?? 0) + 1;

            $lines = [];
            foreach ($this->pick($variants, $this->random->getInt(1, 3)) as $variant) {
                $isDrip = $variant->product->type === ProductType::DripBag;
                $quantity = $isDrip ? [1, 2, 5, 10][$this->random->getInt(0, 3)] : $this->random->getInt(1, 3);
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
                'order_number' => sprintf('RN-%s-%04d', $day, $perDay[$day]),
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
