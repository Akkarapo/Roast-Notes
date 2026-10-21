<?php

namespace Tests\Feature\Catalog;

use App\Enums\BagSize;
use App\Models\CuppingScore;
use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use App\Models\Origin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductShowTest extends TestCase
{
    use RefreshDatabase;

    private FlavorNote $blueberry;

    protected function setUp(): void
    {
        parent::setUp();

        $fruity = FlavorCategory::factory()->create(['slug' => 'fruity', 'name' => 'Fruity']);
        $this->blueberry = FlavorNote::factory()->for(FlavorCategory::factory()->childOf($fruity), 'category')->create(['name' => 'Blueberry']);
    }

    private function guji(array $attributes = []): Product
    {
        $origin = Origin::factory()->create(['country' => 'Ethiopia', 'region' => 'Guji']);
        $product = Product::factory()->withVariants(490)->create([
            'name' => 'Guji Heirloom', 'slug' => 'guji-heirloom', 'origin_id' => $origin->id,
            'farm_name' => 'Metad', 'variety' => 'Heirloom', 'altitude_m' => 2075, 'cqi_ref' => '1',
        ] + $attributes);
        $product->flavorNotes()->attach($this->blueberry);

        return $product;
    }

    public function test_shows_lot_details_sizes_and_notes(): void
    {
        $this->guji();

        $this->get('/products/guji-heirloom')
            ->assertOk()
            ->assertSee('Guji Heirloom')
            ->assertSee('Ethiopia')
            ->assertSee('Metad')
            ->assertSee('Heirloom')
            ->assertSee('2,075')
            ->assertSee('Blueberry')
            ->assertSeeInOrder(['100g', '฿490', '250g', '฿980', '500g', '฿1,470']);
    }

    public function test_sold_out_size_is_disabled(): void
    {
        $product = $this->guji();
        $product->variants()->where('size', BagSize::G250)->update(['stock' => 0]);

        $this->get('/products/guji-heirloom')->assertSee('(หมด)', false);
    }

    public function test_unknown_or_inactive_product_is_404(): void
    {
        $this->guji(['is_active' => false]);

        $this->get('/products/guji-heirloom')->assertNotFound();
        $this->get('/products/does-not-exist')->assertNotFound();
    }

    public function test_cupping_section_only_when_scores_exist(): void
    {
        $product = $this->guji();
        $this->get('/products/guji-heirloom')->assertDontSee('คะแนน cupping');

        CuppingScore::factory()->for($product)->create(['total_points' => 90.58, 'acidity' => 8.75]);

        $this->get('/products/guji-heirloom')
            ->assertSee('คะแนน cupping')
            ->assertSee('90.58')
            ->assertSee('8.75');
    }

    public function test_guest_is_asked_to_log_in_and_member_sees_disabled_cart_button(): void
    {
        $this->guji();

        $this->get('/products/guji-heirloom')->assertSee('เข้าสู่ระบบเพื่อสั่งซื้อ')->assertSee(route('login'));
        $this->actingAs(User::factory()->create())->get('/products/guji-heirloom')->assertSee('เร็ว ๆ นี้');
    }

    public function test_similar_beans_share_a_flavor_category_and_skip_the_same_lot(): void
    {
        $this->guji();
        $twin = Product::factory()->dripBag()->withVariants()->create(['name' => 'Guji Drip', 'cqi_ref' => '1']);
        $twin->flavorNotes()->attach($this->blueberry);
        $berry = Product::factory()->withVariants()->create(['name' => 'Another Berry Lot', 'cqi_ref' => '2']);
        $berry->flavorNotes()->attach($this->blueberry);
        Product::factory()->withVariants()->create(['name' => 'No Notes Lot']);

        $similar = $this->get('/products/guji-heirloom')->viewData('similar')->pluck('name')->all();

        $this->assertSame(['Another Berry Lot'], $similar);
    }
}
