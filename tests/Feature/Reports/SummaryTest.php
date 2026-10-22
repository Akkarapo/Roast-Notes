<?php

namespace Tests\Feature\Reports;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Support\Reports\DateRange;
use App\Support\Reports\SalesOverTime;
use App\Support\Reports\Summary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2026, 10, 23)->setTime(14, 0));
    }

    private function order(string $at, float $total, OrderStatus $status = OrderStatus::Delivered, int $units = 1, float $discount = 0): Order
    {
        $order = Order::factory()->status($status)->create(['placed_at' => $at, 'total' => $total, 'discount_total' => $discount]);
        OrderItem::factory()->for($order)->create(['quantity' => $units]);

        return $order;
    }

    public function test_numbers_and_change(): void
    {
        // this period: 10 Oct – 23 Oct (14 days)
        $this->order('2026-10-10 09:00', 1000, units: 2, discount: 100);
        $this->order('2026-10-23 20:00', 500, OrderStatus::Pending, units: 1);
        $this->order('2026-10-15 09:00', 9999, OrderStatus::Cancelled, units: 9);   // not counted
        $this->order('2026-10-09 23:59', 800);                                       // previous period
        $range = new DateRange(now()->setDate(2026, 10, 10)->startOfDay()->toImmutable(), now()->endOfDay()->toImmutable());

        $this->assertSame(['revenue' => 150000, 'orders' => 2, 'aov' => 75000, 'units' => 3, 'discounts' => 10000], app(Summary::class)->numbers($range));
        $metrics = collect(app(Summary::class)->data($range)['metrics'])->keyBy('key');
        $this->assertSame(87.5, $metrics['revenue']['change']);          // 1,500 vs 800
        $this->assertNull($metrics['discounts']['change']);               // nothing before → "new"
    }

    public function test_this_month_matches_the_dashboard_card(): void
    {
        $this->order('2026-10-02 10:00', 1200);
        $this->order('2026-10-20 10:00', 300, OrderStatus::Shipped);
        $this->order('2026-09-30 10:00', 999);
        $this->order('2026-10-05 10:00', 5000, OrderStatus::Cancelled);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/admin')->assertSeeInOrder(['ยอดขายเดือนนี้', '฿1,500']);
        $this->actingAs($admin)->get('/admin/reports?from=2026-10-01&to=2026-10-23')
            ->assertSee('data-metric="revenue"', false)->assertSeeInOrder(['ยอดขาย', '฿1,500']);
    }

    public function test_sales_over_time_fills_gaps_and_adds_up(): void
    {
        $this->order('2026-10-02 10:00', 100);
        $this->order('2026-10-02 18:00', 200);
        $this->order('2026-10-05 10:00', 50);
        $range = new DateRange(now()->setDate(2026, 10, 1)->startOfDay()->toImmutable(), now()->setDate(2026, 10, 7)->endOfDay()->toImmutable());

        $rows = app(SalesOverTime::class)->rows($range);
        $this->assertSame(['2026-10-01', '2026-10-02', '2026-10-03', '2026-10-04', '2026-10-05', '2026-10-06', '2026-10-07'], array_column($rows, 'period'));
        $this->assertSame([0, 30000, 0, 0, 5000, 0, 0], array_column($rows, 'revenue'));
        $this->assertSame(app(Summary::class)->numbers($range)['revenue'], array_sum(array_column($rows, 'revenue')));

        $months = app(SalesOverTime::class)->rows(DateRange::preset('12m'));
        $this->assertCount(12, $months);
        $this->assertSame('2026-10', end($months)['period']);
        $this->assertSame(35000, end($months)['revenue']);
    }

    public function test_empty_period_shows_empty_states(): void
    {
        $this->actingAs(User::factory()->admin()->create())->get('/admin/reports')
            ->assertOk()->assertSee('ไม่มียอดขายในช่วงนี้');
    }
}
