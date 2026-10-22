<?php

namespace Tests\Feature\Community;

use App\Enums\BrewMethod;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RatingTest extends TestCase
{
    use RefreshDatabase;

    private function reviewed(Product $product, int $rating, BrewMethod $brew, ?User $by = null, ?string $comment = null): Review
    {
        $item = OrderItem::factory()
            ->for(Order::factory()->for($by ?? User::factory()->create(['name' => 'สมชาย ใจดี']))->status(OrderStatus::Delivered))
            ->for($product->variants->first(), 'variant')
            ->create(['size' => '250g', 'grind' => 'drip']);

        return Review::factory()->for($item)->create(['rating' => $rating, 'brew_method' => $brew, 'comment' => $comment]);
    }

    public function test_product_page_shows_average_and_per_brew_method(): void
    {
        $product = Product::factory()->withVariants()->create(['slug' => 'lot']);
        $this->reviewed($product, 5, BrewMethod::Espresso, comment: 'ช็อกโกแลตชัด');
        $this->reviewed($product, 4, BrewMethod::Espresso);
        $this->reviewed($product, 3, BrewMethod::PourOver);
        $this->reviewed(Product::factory()->withVariants()->create(), 1, BrewMethod::Espresso); // other product

        $this->get('/products/lot')
            ->assertSee('data-review-average', false)
            ->assertSeeInOrder(['4.0', '3 รีวิว'])
            ->assertSeeInOrder(['แยกตามวิธีชง', 'Espresso', '4.5', '(2)', 'Pour-over', '3.0', '(1)'])
            ->assertSee('ช็อกโกแลตชัด')
            ->assertSee('สมชาย ใ.')->assertDontSee('สมชาย ใจดี')
            ->assertSee('ซื้อแล้ว · 250g · บดดริป');
    }

    public function test_filter_by_brew_method(): void
    {
        $product = Product::factory()->withVariants()->create(['slug' => 'lot']);
        $this->reviewed($product, 5, BrewMethod::Espresso, comment: 'เอสเปรสโซเข้ม');
        $this->reviewed($product, 3, BrewMethod::PourOver, comment: 'ดริปเปรี้ยว');
        $member = User::factory()->create(['preferred_brew_method' => BrewMethod::PourOver, 'taste_quiz_completed_at' => now()]);

        $this->get('/products/lot?brew=espresso')->assertSee('เอสเปรสโซเข้ม')->assertDontSee('ดริปเปรี้ยว');
        $this->get('/products/lot?brew=aeropress')->assertSee('ยังไม่มีรีวิวที่ชงแบบ AeroPress');
        $this->get('/products/lot?brew=nonsense')->assertSee('เอสเปรสโซเข้ม')->assertSee('ดริปเปรี้ยว');
        $this->actingAs($member)->get('/products/lot')->assertSee('วิธีชงของฉัน');
    }

    public function test_review_this_lot_button_for_unreviewed_delivered_purchase(): void
    {
        $product = Product::factory()->withVariants()->create(['slug' => 'lot']);
        $member = User::factory()->create();
        $item = OrderItem::factory()->for(Order::factory()->for($member)->status(OrderStatus::Delivered))->for($product->variants->first(), 'variant')->create();

        $this->actingAs($member)->get('/products/lot')->assertSee(route('reviews.create', $item));
        Review::factory()->for($item)->create();
        $this->actingAs($member)->get('/products/lot')->assertDontSee('data-review-this-lot', false);
    }

    public function test_cards_show_stars_and_sort_by_rating(): void
    {
        $good = Product::factory()->withVariants()->create(['name' => 'Good Lot']);
        $ok = Product::factory()->withVariants()->create(['name' => 'Ok Lot']);
        Product::factory()->withVariants()->create(['name' => 'Unrated Lot']);
        $this->reviewed($good, 5, BrewMethod::Espresso);
        $this->reviewed($good, 4, BrewMethod::Espresso);
        $this->reviewed($ok, 3, BrewMethod::PourOver);

        $this->get('/products')->assertSee('4.5 (2)')->assertSee('3.0 (1)');
        $this->get('/products?sort=rating')->assertSeeInOrder(['Good Lot', 'Ok Lot', 'Unrated Lot']);
    }

    public function test_ratings_do_not_add_queries_per_card(): void
    {
        foreach (Product::factory()->count(4)->withVariants()->create() as $product) {
            $this->reviewed($product, 4, BrewMethod::Espresso);
        }
        $this->get('/products'); // warm up
        DB::enableQueryLog();
        $this->get('/products');
        $few = count(DB::getQueryLog());

        foreach (Product::factory()->count(4)->withVariants()->create() as $product) {
            $this->reviewed($product, 5, BrewMethod::PourOver);
        }
        DB::flushQueryLog();
        $this->get('/products');
        $this->assertSame($few, count(DB::getQueryLog()));
    }
}
