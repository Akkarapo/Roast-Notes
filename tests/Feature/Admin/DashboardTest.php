<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_match_the_data(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(14)->setTime(15, 0));
        $admin = User::factory()->admin()->create(['created_at' => now()->subYear()]);
        User::factory()->count(2)->create();                               // new this month
        User::factory()->create(['created_at' => now()->subMonths(2)]);    // older

        Order::factory()->status(OrderStatus::Pending)->create(['total' => 500, 'placed_at' => now()->subHour()]);
        Order::factory()->status(OrderStatus::Delivered)->create(['total' => 1000, 'placed_at' => now()->subDays(3)]);
        Order::factory()->status(OrderStatus::Cancelled)->create(['total' => 9999, 'placed_at' => now()->subHour()]);
        Order::factory()->status(OrderStatus::Delivered)->create(['total' => 7777, 'placed_at' => now()->subMonths(2)]);
        Product::factory()->withVariants(stock: 3)->create(['name' => 'Almost Gone']);
        Product::factory()->withVariants(stock: 3)->create(['name' => 'Hidden Lot', 'is_active' => false]);
        Product::factory()->withVariants(stock: 50)->create();

        $users = User::where('role', 'member')->where('created_at', '>=', now()->startOfMonth())->count(); // order factories add members too
        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSeeInOrder(['ยอดขายวันนี้', '฿500', '1 ออเดอร์', 'ยอดขายเดือนนี้', '฿1,500', '2 ออเดอร์', 'รอยืนยัน', '1', 'ขนาดถุงใกล้หมด', '3', 'สมาชิกใหม่เดือนนี้', (string) $users])
            ->assertSee('Almost Gone')->assertDontSee('Hidden Lot');
    }
}
