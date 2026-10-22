<?php

namespace Tests\Feature\Loyalty;

use App\Actions\RecalculateMemberTier;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\Loyalty\LoyaltyStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateMemberTierTest extends TestCase
{
    use RefreshDatabase;

    private function order(User $user, float $total, OrderStatus $status = OrderStatus::Delivered): Order
    {
        return Order::factory()->for($user)->status($status)->create(['total' => $total]);
    }

    private function recalc(User $user): string
    {
        return app(RecalculateMemberTier::class)($user)->slug;
    }

    public function test_only_delivered_orders_count(): void
    {
        $user = User::factory()->create();
        $this->order($user, 20000);
        foreach ([OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::InTransit, OrderStatus::Cancelled] as $status) {
            $this->order($user, 10000, $status);
        }

        $this->assertSame(2000000, RecalculateMemberTier::spendOf($user));
        $this->assertSame('bean', $this->recalc($user));
    }

    public function test_reaching_a_threshold_exactly_moves_up(): void
    {
        $user = User::factory()->create();
        $this->order($user, 24999.99);
        $this->assertSame('bean', $this->recalc($user));

        $this->order($user, 0.01);
        $this->assertSame('barista', $this->recalc($user));

        $this->order($user, 25000);
        $this->assertSame('roaster-master', $this->recalc($user));
        $this->assertSame('roaster-master', $user->fresh()->memberTier->slug);
    }

    public function test_tier_never_moves_down(): void
    {
        $user = User::factory()->tier('roaster-master')->create();
        $this->order($user, 100);

        $this->assertSame('roaster-master', $this->recalc($user));
    }

    public function test_mark_delivered_moves_the_member_up(): void
    {
        $user = User::factory()->create();
        $this->order($user, 20000);
        $pending = $this->order($user, 6000, OrderStatus::InTransit);

        $pending->markDelivered();

        $this->assertSame(OrderStatus::Delivered, $pending->fresh()->status);
        $this->assertSame('barista', $user->fresh()->memberTier->slug);
    }

    public function test_status_shows_distance_to_next_tier(): void
    {
        $bean = User::factory()->create();
        $this->order($bean, 23160);
        $status = LoyaltyStatus::for($bean);
        $this->assertSame('barista', $status->next->slug);
        $this->assertSame(184000, $status->remaining());
        $this->assertSame(92, $status->progress());

        $top = User::factory()->tier('roaster-master')->create();
        $this->assertNull(LoyaltyStatus::for($top)->next);
        $this->assertSame(0, LoyaltyStatus::for($top)->remaining());
        $this->assertSame(100, LoyaltyStatus::for($top)->progress());
    }
}
