<?php

namespace Tests\Feature\Community;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewEligibilityTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $this->variant = ProductVariant::factory()->for(Product::factory())->create();
    }

    private function item(OrderStatus $status = OrderStatus::Delivered, ?User $owner = null): OrderItem
    {
        return OrderItem::factory()
            ->for(Order::factory()->for($owner ?? $this->member)->status($status))
            ->for($this->variant, 'variant')
            ->create();
    }

    private function review(OrderItem $item)
    {
        return $this->actingAs($this->member)->post("/order-items/{$item->id}/review", ['rating' => 5, 'brew_method' => 'espresso']);
    }

    public function test_delivered_items_can_be_reviewed(): void
    {
        $item = $this->item();

        $this->actingAs($this->member)->get("/orders/{$item->order->order_number}")->assertSee('data-review-create', false);
        $this->actingAs($this->member)->get("/order-items/{$item->id}/review/create")->assertOk()->assertSee('ชงด้วยวิธีไหน?');
        $this->review($item)->assertRedirect("/orders/{$item->order->order_number}")->assertSessionHas('flash', 'ขอบคุณสำหรับรีวิว!');
        $this->assertSame(5, $item->review->rating);
    }

    public function test_other_statuses_cannot_be_reviewed(): void
    {
        foreach ([OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Shipped, OrderStatus::InTransit, OrderStatus::Cancelled] as $status) {
            $item = $this->item($status);
            $this->actingAs($this->member)->get("/orders/{$item->order->order_number}")->assertDontSee('data-review-create', false);
            $this->actingAs($this->member)->get("/order-items/{$item->id}/review/create")->assertForbidden();
            $this->review($item)->assertForbidden();
        }
        $this->assertSame(0, Review::count());
    }

    public function test_someone_elses_item_is_not_found(): void
    {
        $item = $this->item(owner: User::factory()->create());

        $this->actingAs($this->member)->get("/order-items/{$item->id}/review/create")->assertNotFound();
        $this->review($item)->assertNotFound();
    }

    public function test_an_item_is_reviewed_once_but_a_repurchase_can_be_reviewed_again(): void
    {
        $first = $this->item();
        $this->review($first);
        $this->review($first)->assertForbidden();
        $this->actingAs($this->member)->get("/orders/{$first->order->order_number}")->assertSee('data-review-edit', false);

        $again = $this->item(); // bought the same bag again
        $this->review($again)->assertRedirect();
        $this->assertSame(2, Review::forProduct($this->variant->product_id)->count());
    }

    public function test_guests_go_to_login(): void
    {
        $this->get("/order-items/{$this->item()->id}/review/create")->assertRedirect('/login');
    }
}
