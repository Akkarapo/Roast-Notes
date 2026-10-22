<?php

namespace Tests\Feature\Promotions;

use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Promotion;
use Database\Seeders\PromotionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PromoBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_cards_and_product_page_show_active_promotions_only(): void
    {
        $light = Product::factory()->withVariants()->create(['name' => 'Light Lot', 'slug' => 'light-lot']);
        $plain = Product::factory()->withVariants()->create(['name' => 'Plain Lot', 'slug' => 'plain-lot']);
        Promotion::factory()->coupon('LIGHTROAST15', 15)->forProducts([$light])->create();
        Promotion::factory()->coupon('SUMMER20', 20)->expired()->forProducts([$light, $plain])->create();

        $this->get('/products')->assertSee('ลด 15% · LIGHTROAST15')->assertDontSee('SUMMER20');
        $this->get('/products/light-lot')->assertSee('ลด 15% · LIGHTROAST15');
        $this->get('/products/plain-lot')->assertDontSee('data-promo-badge', false);
    }

    public function test_on_promotion_filter(): void
    {
        $drip = Product::factory()->dripBag()->withVariants()->create(['name' => 'Drip Deal']);
        Product::factory()->withVariants()->create(['name' => 'No Deal']);
        Promotion::factory()->buyXGetY(10, 2)->forProducts([$drip])->create();

        $this->get('/products?promo=1')->assertSee('Drip Deal')->assertSee('ซื้อ 10 แถม 2')->assertDontSee('No Deal')->assertSee('ล้างตัวกรอง');
    }

    public function test_badge_queries_do_not_grow_with_cards(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/products')->assertOk();

            return count(DB::getQueryLog());
        };
        $first = Product::factory()->withVariants()->create();
        Promotion::factory()->coupon()->forProducts([$first])->create();
        $count(); // warm-up (session row)
        $few = $count();

        $more = Product::factory()->count(8)->withVariants()->create();
        Promotion::factory()->coupon('MORE')->forProducts($more)->create();

        $this->assertSame($few, $count());
    }

    public function test_seeder_creates_the_four_demo_promotions(): void
    {
        Product::factory()->withVariants()->create(['roast_level' => RoastLevel::Light]);
        Product::factory()->withVariants()->create(['roast_level' => RoastLevel::Dark]);
        Product::factory()->dripBag()->withVariants()->create();

        $this->seed(PromotionSeeder::class);

        $this->assertEqualsCanonicalizing(['LIGHTROAST15', 'ROASTER50', 'SUMMER20'], Coupon::pluck('code')->all());
        $light = Coupon::where('code', 'LIGHTROAST15')->first();
        $this->assertSame([100, 1], [$light->usage_limit, $light->per_user_limit]);
        $this->assertSame(1, $light->promotion->products()->count());
        $this->assertFalse(Coupon::where('code', 'SUMMER20')->first()->promotion->isCurrentlyActive());
        $this->assertSame(
            Product::ofType(ProductType::DripBag)->pluck('id')->all(),
            Promotion::where('type', 'buy_x_get_y')->sole()->products->pluck('id')->all()
        );
    }
}
