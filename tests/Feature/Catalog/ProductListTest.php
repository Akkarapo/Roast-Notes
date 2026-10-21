<?php

namespace Tests\Feature\Catalog;

use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\CuppingScore;
use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use App\Models\Origin;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductListTest extends TestCase
{
    use RefreshDatabase;

    private FlavorNote $blueberry;

    private FlavorNote $chocolate;

    protected function setUp(): void
    {
        parent::setUp();

        $fruity = FlavorCategory::factory()->create(['slug' => 'fruity', 'name' => 'Fruity']);
        $cocoa = FlavorCategory::factory()->create(['slug' => 'nutty-cocoa', 'name' => 'Nutty/Cocoa']);
        $this->blueberry = FlavorNote::factory()->for(FlavorCategory::factory()->childOf($fruity), 'category')->create(['name' => 'Blueberry']);
        $this->chocolate = FlavorNote::factory()->for(FlavorCategory::factory()->childOf($cocoa), 'category')->create(['name' => 'Dark chocolate']);
    }

    private function product(string $name, array $attributes = [], array $notes = [], int $price = 200): Product
    {
        $product = Product::factory()->withVariants($price)->create(['name' => $name, 'slug' => str($name)->slug()] + $attributes);
        $product->flavorNotes()->attach(collect($notes)->pluck('id'));

        return $product;
    }

    public function test_lists_only_active_products(): void
    {
        $this->product('Active Bean');
        $this->product('Hidden Bean', ['is_active' => false]);

        $this->get('/products')->assertOk()->assertSee('Active Bean')->assertDontSee('Hidden Bean');
    }

    public function test_paginates_twelve_per_page_and_keeps_filters(): void
    {
        foreach (range(1, 14) as $i) {
            $this->product("Light Lot $i", ['roast_level' => RoastLevel::Light]);
        }

        $page1 = $this->get('/products?roast[]=light');
        $this->assertCount(12, $page1->viewData('products'));
        $page1->assertSee('roast%5B0%5D=light&amp;page=2', false);

        $this->assertCount(2, $this->get('/products?roast[]=light&page=2')->viewData('products'));
    }

    public function test_filters_by_flavor_category_with_or_inside_the_group(): void
    {
        $this->product('Berry Bomb', notes: [$this->blueberry]);
        $this->product('Choco Bean', notes: [$this->chocolate]);
        $this->product('Plain Bean');

        $this->get('/products?flavor[]=fruity')->assertSee('Berry Bomb')->assertDontSee('Choco Bean')->assertDontSee('Plain Bean');
        $this->get('/products?flavor[]=fruity&flavor[]=nutty-cocoa')->assertSee('Berry Bomb')->assertSee('Choco Bean')->assertDontSee('Plain Bean');
    }

    public function test_filters_by_roast_type_and_country(): void
    {
        $thai = Origin::factory()->create(['country' => 'Thailand', 'region' => 'Chiang Rai']);
        $this->product('Dark Thai', ['roast_level' => RoastLevel::Dark, 'origin_id' => $thai->id]);
        $this->product('Light Thai Drip', ['roast_level' => RoastLevel::Light, 'origin_id' => $thai->id, 'type' => ProductType::DripBag]);
        $colombia = Origin::factory()->create(['country' => 'Colombia', 'region' => 'Huila']);
        $this->product('Light Other', ['roast_level' => RoastLevel::Light, 'origin_id' => $colombia->id]);

        $this->get('/products?roast[]=dark')->assertSee('Dark Thai')->assertDontSee('Light Other');
        $this->get('/products?type=drip_bag')->assertSee('Light Thai Drip')->assertDontSee('Dark Thai');
        $this->get('/products?country=Thailand')->assertSee('Dark Thai')->assertSee('Light Thai Drip')->assertDontSee('Light Other');
    }

    public function test_different_filter_groups_are_combined_with_and(): void
    {
        $this->product('Light Berry', ['roast_level' => RoastLevel::Light], [$this->blueberry]);
        $this->product('Dark Berry', ['roast_level' => RoastLevel::Dark], [$this->blueberry]);
        $this->product('Light Choco', ['roast_level' => RoastLevel::Light], [$this->chocolate]);

        $this->get('/products?flavor[]=fruity&roast[]=light')
            ->assertSee('Light Berry')->assertDontSee('Dark Berry')->assertDontSee('Light Choco');
    }

    public function test_malformed_query_is_ignored(): void
    {
        $this->product('Any Bean');

        $this->get('/products?flavor=chilli&roast[]=burnt&type[]=x&country[]=y&sort=drop-table&page=abc')
            ->assertOk()
            ->assertSee('Any Bean');
    }

    public function test_sorts_by_price_score_and_roast_date(): void
    {
        $cheap = $this->product('Cheap Bean', ['roast_date' => now()->subDays(30)], price: 100);
        $pricey = $this->product('Pricey Bean', ['roast_date' => now()->subDays(8)], price: 500);
        CuppingScore::factory()->for($cheap)->create(['total_points' => 88]);
        CuppingScore::factory()->for($pricey)->create(['total_points' => 83]);

        $names = fn (string $sort) => $this->get("/products?sort=$sort")->viewData('products')->pluck('name')->all();

        $this->assertSame(['Cheap Bean', 'Pricey Bean'], $names('price_asc'));
        $this->assertSame(['Pricey Bean', 'Cheap Bean'], $names('price_desc'));
        $this->assertSame(['Cheap Bean', 'Pricey Bean'], $names('score'));
        $this->assertSame(['Pricey Bean', 'Cheap Bean'], $names('newest'));
    }

    public function test_card_shows_from_price_notes_and_sold_out_badge(): void
    {
        $this->product('Stocked Bean', notes: [$this->blueberry], price: 190);
        Product::factory()->withVariants(200, stock: 0)->create(['name' => 'Empty Bean']);

        $this->get('/products')
            ->assertSee('฿190')
            ->assertSee('Blueberry')
            ->assertSeeInOrder(['Empty Bean', 'หมดชั่วคราว'], false);
    }

    public function test_empty_result_offers_to_clear_filters(): void
    {
        $this->product('Medium Bean', ['roast_level' => RoastLevel::Medium]);

        $this->get('/products?roast[]=dark')
            ->assertSee('ไม่พบเมล็ดที่ตรงกับตัวกรอง')
            ->assertSee('ล้างตัวกรอง');
    }

    public function test_query_count_does_not_grow_with_products(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            $this->get('/products?flavor[]=fruity&sort=price_asc')->assertOk();

            return count(DB::getQueryLog());
        };

        $this->product('One', notes: [$this->blueberry]);
        $few = $count();

        foreach (range(2, 12) as $i) {
            $this->product("Bean $i", notes: [$this->blueberry]);
        }

        $this->assertSame($few, $count());
        $this->assertLessThanOrEqual(12, $few);
    }
}
