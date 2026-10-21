<?php

namespace Tests\Feature\Catalog;

use App\Enums\BagSize;
use App\Enums\ProductType;
use App\Models\CuppingScore;
use App\Models\FlavorCategory;
use App\Models\FlavorNote;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_bean_gets_three_sizes_and_drip_bag_one_sachet(): void
    {
        $bean = Product::factory()->withVariants()->create();
        $drip = Product::factory()->dripBag()->withVariants()->create();

        $this->assertSame(['100g', '250g', '500g'], $bean->variants->map(fn ($v) => $v->size->value)->all());
        $this->assertSame(ProductType::DripBag, $drip->type);
        $this->assertSame([BagSize::Sachet], $drip->variants->pluck('size')->all());
    }

    public function test_one_variant_per_size_per_product(): void
    {
        $product = Product::factory()->withVariants()->create();

        $this->expectException(QueryException::class);
        ProductVariant::factory()->for($product)->create(['size' => BagSize::G250]);
    }

    public function test_slug_is_unique(): void
    {
        Product::factory()->create(['slug' => 'guji-hambela']);

        $this->expectException(QueryException::class);
        Product::factory()->create(['slug' => 'guji-hambela']);
    }

    public function test_cupping_score_is_one_to_one(): void
    {
        $product = Product::factory()->create();
        CuppingScore::factory()->for($product)->create(['total_points' => 86.25]);

        $this->assertEquals(86.25, $product->cupping->total_points);

        $this->expectException(QueryException::class);
        CuppingScore::factory()->for($product)->create();
    }

    public function test_flavor_categories_are_recursive(): void
    {
        $fruity = FlavorCategory::factory()->create(['slug' => 'fruity']);
        $berry = FlavorCategory::factory()->childOf($fruity)->create(['slug' => 'berry']);
        $note = FlavorNote::factory()->for($berry, 'category')->create(['name' => 'Blueberry']);

        $this->assertTrue($note->category->parent->is($fruity));
        $this->assertTrue($fruity->children->first()->is($berry));
        $this->assertSame(['fruity'], FlavorCategory::topLevel()->pluck('slug')->all());
    }

    public function test_deleting_a_product_removes_its_variants_scores_and_note_links(): void
    {
        $product = Product::factory()->withVariants()->create();
        CuppingScore::factory()->for($product)->create();
        $product->flavorNotes()->attach(FlavorNote::factory()->create());

        $product->delete();

        $this->assertDatabaseCount('product_variants', 0);
        $this->assertDatabaseCount('cupping_scores', 0);
        $this->assertSame(0, DB::table('product_flavor_notes')->count());
        $this->assertDatabaseCount('flavor_notes', 1);
    }
}
