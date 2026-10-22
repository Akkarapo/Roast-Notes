<?php

namespace Tests\Feature\Community;

use App\Enums\BrewMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private OrderItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create(['preferred_brew_method' => BrewMethod::AeroPress]);
        $this->item = OrderItem::factory()
            ->for(Order::factory()->for($this->member)->status(OrderStatus::Delivered))
            ->for(ProductVariant::factory()->for(Product::factory()), 'variant')
            ->create();
    }

    public function test_form_preselects_brew_method_from_taste_profile(): void
    {
        $this->actingAs($this->member)->get("/order-items/{$this->item->id}/review/create")
            ->assertSee('value="aeropress" checked', false);
    }

    public function test_drip_bags_preselect_drip_bag(): void
    {
        $this->item->variant->product->update(['type' => 'drip_bag']);

        $this->actingAs($this->member)->get("/order-items/{$this->item->id}/review/create")
            ->assertSee('value="drip_bag" checked', false);
    }

    public function test_validation(): void
    {
        $url = "/order-items/{$this->item->id}/review";
        $this->actingAs($this->member)->post($url, ['rating' => 0, 'brew_method' => 'espresso'])->assertSessionHasErrors('rating');
        $this->actingAs($this->member)->post($url, ['rating' => 6, 'brew_method' => 'espresso'])->assertSessionHasErrors('rating');
        $this->actingAs($this->member)->post($url, ['rating' => 4])->assertSessionHasErrors('brew_method');
        $this->actingAs($this->member)->post($url, ['rating' => 4, 'brew_method' => 'siphon'])->assertSessionHasErrors('brew_method');
        $this->actingAs($this->member)->post($url, ['rating' => 4, 'brew_method' => 'espresso', 'comment' => str_repeat('ก', 1001)])->assertSessionHasErrors('comment');
        $this->assertSame(0, Review::count());

        // comment is optional, and an order_item_id in the form is ignored
        $other = OrderItem::factory()->create();
        $this->actingAs($this->member)->post($url, ['rating' => 4, 'brew_method' => 'espresso', 'order_item_id' => $other->id])->assertSessionHasNoErrors();
        $this->assertSame($this->item->id, Review::sole()->order_item_id);
    }

    public function test_edit_and_delete_own_review(): void
    {
        $review = Review::factory()->for($this->item)->create(['rating' => 3, 'brew_method' => BrewMethod::PourOver, 'comment' => 'เปรี้ยวไป']);
        $this->travel(1)->minutes();

        $this->actingAs($this->member)->get("/reviews/{$review->id}/edit")->assertOk()->assertSee('เปรี้ยวไป');
        $this->actingAs($this->member)->patch("/reviews/{$review->id}", ['rating' => 5, 'brew_method' => 'espresso', 'comment' => 'ชงเอสเปรสโซแล้วดีมาก'])
            ->assertRedirect()->assertSessionHas('flash', 'แก้รีวิวแล้ว');
        $review->refresh();
        $this->assertSame([5, BrewMethod::Espresso, 'ชงเอสเปรสโซแล้วดีมาก'], [$review->rating, $review->brew_method, $review->comment]);
        $this->assertTrue($review->wasEdited());

        $this->actingAs($this->member)->delete("/reviews/{$review->id}")->assertSessionHas('flash', 'ลบรีวิวแล้ว');
        $this->assertModelMissing($review);
        $this->actingAs($this->member)->get("/order-items/{$this->item->id}/review/create")->assertOk(); // can write a new one
    }

    public function test_cannot_touch_someone_elses_review(): void
    {
        $review = Review::factory()->create();

        $this->actingAs($this->member)->get("/reviews/{$review->id}/edit")->assertNotFound();
        $this->actingAs($this->member)->patch("/reviews/{$review->id}", ['rating' => 1, 'brew_method' => 'espresso'])->assertNotFound();
        $this->actingAs($this->member)->delete("/reviews/{$review->id}")->assertNotFound();
        $this->assertModelExists($review);
    }
}
