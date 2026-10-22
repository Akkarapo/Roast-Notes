<?php

namespace Tests\Feature\Admin;

use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use App\Models\OrderItem;
use App\Models\Origin;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Origin $origin;

    private array $notes;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->origin = Origin::factory()->create(['country' => 'Thailand', 'region' => 'Chiang Rai']);
        $fruity = FlavorCategory::factory()->create(['slug' => 'fruity', 'name' => 'Fruity']);
        $berry = FlavorCategory::factory()->create(['slug' => 'berry', 'name' => 'Berry', 'parent_id' => $fruity->id]);
        $this->notes = FlavorNote::factory()->count(2)->sequence(['name' => 'Blueberry'], ['name' => 'Strawberry'])->create(['flavor_category_id' => $berry->id])->pluck('id')->all();
    }

    private function payload(array $override = []): array
    {
        return array_replace_recursive([
            'name' => 'Doi Chang Natural', 'nickname' => 'Berry Bright', 'slug' => '', 'type' => 'bean',
            'origin_id' => $this->origin->id, 'process' => 'natural', 'roast_level' => 'light', 'roast_date' => today()->toDateString(),
            'altitude_m' => 1400, 'is_active' => '1', 'flavor_notes' => $this->notes,
            'variants' => [
                '100g' => ['enabled' => '1', 'price' => '220', 'stock' => '10'],
                '250g' => ['enabled' => '1', 'price' => '480', 'stock' => '8'],
                '500g' => ['enabled' => '1', 'price' => '890', 'stock' => '4'],
                'sachet' => ['enabled' => '0', 'price' => '', 'stock' => ''],
            ],
        ], $override);
    }

    public function test_create_product_with_three_sizes_and_buy_it(): void
    {
        $this->actingAs($this->admin)->get('/admin/products/create')->assertOk()->assertSee('Blueberry');
        $this->actingAs($this->admin)->post('/admin/products', $this->payload())->assertSessionHasNoErrors()->assertRedirect('/admin/products/doi-chang-natural/edit');

        $product = Product::where('slug', 'doi-chang-natural')->sole();
        $this->assertSame(['100g' => '220.00', '250g' => '480.00', '500g' => '890.00'], $product->variants->sortBy('weight_g')->mapWithKeys(fn ($v) => [$v->size->value => $v->price])->all());
        $this->assertEqualsCanonicalizing($this->notes, $product->flavorNotes->pluck('id')->all());

        $member = User::factory()->create();
        $this->get('/products/doi-chang-natural')->assertOk()->assertSee('Berry Bright');
        $this->actingAs($member)->post('/cart/items', ['variant_id' => $product->variants->first()->id, 'quantity' => 1, 'grind' => 'whole_bean'])->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->get('/admin/products')->assertSee('Doi Chang Natural')->assertSee('500g: 4');
    }

    public function test_validation(): void
    {
        Product::factory()->create(['slug' => 'taken']);
        $post = fn ($override) => $this->actingAs($this->admin)->post('/admin/products', $this->payload($override));

        $post(['slug' => 'taken'])->assertSessionHasErrors('slug');
        $this->actingAs($this->admin)->post('/admin/products', ['flavor_notes' => []] + $this->payload())->assertSessionHasErrors('flavor_notes');
        $post(['roast_date' => today()->addDay()->toDateString()])->assertSessionHasErrors('roast_date');
        $post(['variants' => ['100g' => ['enabled' => '0'], '250g' => ['enabled' => '0'], '500g' => ['enabled' => '0']]])->assertSessionHasErrors('variants');
        $post(['variants' => ['sachet' => ['enabled' => '1', 'price' => '40', 'stock' => '5']]])->assertSessionHasErrors('variants'); // beans don't sell sachets
        $post(['variants' => ['100g' => ['price' => '']]])->assertSessionHasErrors('variants.100g.price');
        $this->assertSame(1, Product::count());
    }

    public function test_edit_price_stock_and_remove_an_unsold_size(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', $this->payload());
        $product = Product::sole();

        $this->actingAs($this->admin)->put("/admin/products/{$product->slug}", $this->payload([
            'variants' => ['100g' => ['price' => '230', 'stock' => '0'], '500g' => ['enabled' => '0']],
            'exclusive_until' => now()->addDays(7)->format('Y-m-d\TH:i'),
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame(['100g', '250g'], $product->variants->sortBy('weight_g')->map->size->map->value->values()->all());
        $this->assertSame(['230.00', 0], [$product->variants->firstWhere('size.value', '100g')->price, $product->variants->firstWhere('size.value', '100g')->stock]);
        $this->assertTrue($product->isExclusiveNow());
    }

    public function test_ordered_size_and_product_cannot_be_removed(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', $this->payload());
        $product = Product::sole();
        OrderItem::factory()->for($product->variants->firstWhere('size.value', '500g'), 'variant')->create();

        $this->actingAs($this->admin)->put("/admin/products/{$product->slug}", $this->payload(['variants' => ['500g' => ['enabled' => '0']]]))
            ->assertSessionHasErrors('variants');
        $this->assertSame(3, $product->variants()->count());

        $this->actingAs($this->admin)->get("/admin/products/{$product->slug}/edit")->assertDontSee('data-delete', false);
        $this->actingAs($this->admin)->delete("/admin/products/{$product->slug}")->assertSessionHasErrors('product');
        $this->assertModelExists($product);
    }

    public function test_never_ordered_product_can_be_deleted_and_deactivated_hides_from_shop(): void
    {
        $this->actingAs($this->admin)->post('/admin/products', $this->payload());
        $product = Product::sole();

        $this->actingAs($this->admin)->put("/admin/products/{$product->slug}", $this->payload(['is_active' => '0']));
        $this->get('/products/doi-chang-natural')->assertNotFound();
        $this->get('/products')->assertDontSee('Doi Chang Natural');

        $this->actingAs($this->admin)->delete("/admin/products/{$product->slug}")->assertRedirect('/admin/products');
        $this->assertModelMissing($product);
    }

    public function test_filters(): void
    {
        Product::factory()->withVariants(stock: 2)->create(['name' => 'Low Lot']);
        Product::factory()->withVariants(stock: 50)->create(['name' => 'Full Lot', 'is_active' => false]);

        $this->actingAs($this->admin)->get('/admin/products?state=low')->assertSee('Low Lot')->assertDontSee('Full Lot');
        $this->actingAs($this->admin)->get('/admin/products?state=inactive')->assertSee('Full Lot')->assertDontSee('Low Lot');
        $this->actingAs($this->admin)->get('/admin/products?q=Full')->assertSee('Full Lot')->assertDontSee('Low Lot');
    }
}
