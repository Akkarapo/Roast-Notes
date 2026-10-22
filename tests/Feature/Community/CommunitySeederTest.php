<?php

namespace Tests\Feature\Community;

use App\Enums\BrewMethod;
use App\Enums\OrderStatus;
use App\Enums\ProductType;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_reviews_and_wishlists(): void
    {
        $this->seed();

        $reviews = Review::with('orderItem.order', 'orderItem.variant.product')->get();
        $this->assertGreaterThan(50, $reviews->count());
        $this->assertTrue($reviews->every(fn (Review $r) => $r->orderItem->order->status === OrderStatus::Delivered));
        $this->assertTrue($reviews->every(fn (Review $r) => $r->created_at->lte(now())));
        $this->assertTrue($reviews->filter(fn (Review $r) => $r->orderItem->variant->product->type === ProductType::DripBag)
            ->every(fn (Review $r) => $r->brew_method === BrewMethod::DripBag));
        $this->assertSame($reviews->count(), $reviews->pluck('order_item_id')->unique()->count());

        User::where('role', 'member')->each(fn (User $u) => $this->assertGreaterThanOrEqual(3, $u->wishlist()->count()));
    }
}
