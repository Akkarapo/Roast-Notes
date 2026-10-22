<?php

namespace Tests\Feature\Community;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_wishlist_pair_is_unique(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $user->wishlist()->attach($product);

        $this->assertTrue($product->wishedBy->contains($user));
        $this->expectException(QueryException::class);
        $user->wishlist()->attach($product);
    }

    public function test_one_review_per_order_item(): void
    {
        $item = OrderItem::factory()->create();
        Review::factory()->for($item)->create();

        $this->expectException(QueryException::class);
        Review::factory()->for($item)->create();
    }

    public function test_rating_outside_1_to_5_is_rejected_by_the_database(): void
    {
        $this->expectException(QueryException::class);
        Review::factory()->create(['rating' => 6]);
    }

    public function test_reviewer_and_product_come_through_the_order_item(): void
    {
        $user = User::factory()->create(['name' => 'สมชาย ใจดี']);
        $product = Product::factory()->create();
        $item = OrderItem::factory()
            ->for(Order::factory()->for($user))
            ->for(ProductVariant::factory()->for($product), 'variant')
            ->create();
        $review = Review::factory()->for($item)->create();
        Review::factory()->create(); // someone else's, another product

        $this->assertTrue($review->author()->is($user));
        $this->assertSame('สมชาย ใ.', $user->shortName());
        $this->assertSame([$review->id], $product->reviewsQuery()->pluck('id')->all());
        $this->assertSame([$review->id], Review::byUser($user)->pluck('id')->all());
    }

    public function test_deleting_the_order_removes_its_reviews(): void
    {
        $review = Review::factory()->create();
        $review->orderItem->order->delete();

        $this->assertModelMissing($review);
    }
}
