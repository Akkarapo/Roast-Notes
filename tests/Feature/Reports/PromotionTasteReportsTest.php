<?php

namespace Tests\Feature\Reports;

use App\Enums\OrderStatus;
use App\Enums\RoastLevel;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Promotion;
use App\Models\User;
use App\Support\Reports\Discounts;
use App\Support\Reports\TasteVsSales;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Reports\Concerns\SellsThings;
use Tests\TestCase;

class PromotionTasteReportsTest extends TestCase
{
    use RefreshDatabase, SellsThings;

    private function discount(?Promotion $promotion, string $source, string $label, float $amount, OrderStatus $status = OrderStatus::Delivered, ?Order $order = null): void
    {
        OrderDiscount::factory()->for($order ?? Order::factory()->status($status)->state(['placed_at' => '2026-10-10 10:00']))
            ->create(['promotion_id' => $promotion?->id, 'source' => $source, 'label' => $label, 'amount' => $amount]);
    }

    public function test_discounts_per_promotion_and_tier(): void
    {
        $coupon = Promotion::factory()->coupon('LIGHT15', 15)->create(['name' => 'คั่วอ่อนลด 15%']);
        $deal = Promotion::factory()->buyXGetY(10, 2)->create(['name' => 'ซื้อ 10 แถม 2']);
        $order = Order::factory()->state(['placed_at' => '2026-10-11 10:00'])->create();
        $this->discount($coupon, 'coupon', 'LIGHT15 ลด 15%', 45, order: $order);
        $this->discount($deal, 'buy_x_get_y', 'ซื้อ 10 แถม 2', 80, order: $order);
        $this->discount($coupon, 'coupon', 'LIGHT15 ลด 15%', 30);
        $this->discount(null, 'loyalty', 'สมาชิก Barista ลด 5%', 20);
        $this->discount($coupon, 'coupon', 'LIGHT15 ลด 15%', 999, OrderStatus::Cancelled);   // not counted

        $rows = collect(app(Discounts::class)->rows($this->october()))->keyBy('name');
        $this->assertSame([2, 7500], [$rows['คั่วอ่อนลด 15%']['orders'], $rows['คั่วอ่อนลด 15%']['amount']]);
        $this->assertSame([1, 8000], [$rows['ซื้อ 10 แถม 2']['orders'], $rows['ซื้อ 10 แถม 2']['amount']]);
        $this->assertSame(['loyalty', 2000], [$rows['สมาชิก Barista ลด 5%']['source'], $rows['สมาชิก Barista ลด 5%']['amount']]);
    }

    public function test_taste_liked_vs_sold(): void
    {
        [$fruity, [$berry]] = $this->group('fruity', 'Fruity', ['Berry']);
        [$cocoa, [$choc]] = $this->group('nutty-cocoa', 'Nutty/Cocoa', ['Chocolate']);
        $this->group('floral', 'Floral', ['Jasmine']);
        User::factory()->create()->tastePreferences()->attach([$fruity->id]);
        User::factory()->create()->tastePreferences()->attach([$fruity->id, $cocoa->id]);
        User::factory()->create()->tastePreferences()->attach([$fruity->id]);
        User::factory()->create()->tastePreferences()->attach([$cocoa->id]);
        $this->sell($this->lot('Choc lot', RoastLevel::Dark, [$choc]), 3, 900);
        $this->sell($this->lot('Berry lot', RoastLevel::Light, [$berry]), 1, 300);

        $rows = collect(app(TasteVsSales::class)->rows($this->october()))->keyBy('name');
        $this->assertSame([3, 75.0, 1, 25.0], array_values(array_intersect_key($rows['Fruity'], array_flip(['members', 'liked', 'units', 'sold']))));
        $this->assertSame([2, 50.0, 3, 75.0], array_values(array_intersect_key($rows['Nutty/Cocoa'], array_flip(['members', 'liked', 'units', 'sold']))));
        $this->assertSame([0, 0.0, 0, 0.0], array_values(array_intersect_key($rows['Floral'], array_flip(['members', 'liked', 'units', 'sold']))));
    }
}
