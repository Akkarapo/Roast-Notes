<?php

namespace Tests\Feature\Catalog;

use App\Enums\BagSize;
use App\Enums\ProductType;
use App\Enums\RoastLevel;
use App\Models\FlavorCategory;
use App\Models\Origin;
use App\Models\Product;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\FlavorTaxonomySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([FlavorTaxonomySeeder::class, CatalogSeeder::class]);
    }

    public function test_seeds_the_wcr_taxonomy(): void
    {
        $this->assertSame(
            ['fruity', 'floral', 'sweet', 'nutty-cocoa', 'sour-fermented'],
            FlavorCategory::topLevel()->orderBy('id')->pluck('slug')->all()
        );
        $this->assertDatabaseCount('flavor_notes', 36);
    }

    public function test_seeds_40_beans_and_8_drip_bags(): void
    {
        $this->assertSame(40, Product::ofType(ProductType::Bean)->count());
        $this->assertSame(8, Product::ofType(ProductType::DripBag)->count());
    }

    public function test_beans_have_three_sizes_and_drip_bags_one_sachet(): void
    {
        Product::with('variants')->get()->each(function (Product $product) {
            $expected = $product->type === ProductType::DripBag ? [BagSize::Sachet] : BagSize::forBeans();
            $this->assertSame($expected, $product->variants->pluck('size')->all(), $product->name);
        });
    }

    public function test_every_roast_level_is_present(): void
    {
        foreach (RoastLevel::cases() as $level) {
            $this->assertGreaterThan(0, Product::roastLevels([$level])->count(), $level->value);
        }
    }

    public function test_every_product_has_two_to_four_notes_and_cupping_scores(): void
    {
        Product::with(['flavorNotes', 'cupping'])->get()->each(function (Product $product) {
            $this->assertContains($product->flavorNotes->count(), [2, 3, 4], $product->name);
            $this->assertNotNull($product->cupping, $product->name);
        });
    }

    public function test_includes_thai_lots_and_many_countries(): void
    {
        $this->assertGreaterThan(0, Product::fromCountry('Thailand')->count());
        $this->assertGreaterThanOrEqual(10, Product::join('origins', 'origins.id', '=', 'products.origin_id')->distinct()->count('origins.country'));
    }

    public function test_real_cqi_scores_are_kept(): void
    {
        $guji = Product::where('cqi_ref', '1')->where('type', 'bean')->firstOrFail();

        $this->assertEquals(90.58, $guji->cupping->total_points);
        $this->assertSame('Ethiopia', $guji->origin->country);
    }

    public function test_seeding_twice_gives_the_same_catalog(): void
    {
        $snapshot = fn () => Product::with(['variants', 'flavorNotes'])->orderBy('cqi_ref')->orderBy('type')->get()
            ->map(fn (Product $p) => [$p->slug, $p->roast_level, $p->roast_date->toDateString(), $p->flavorNotes->pluck('name')->sort()->values()->all(), $p->variants->pluck('price')->all()])
            ->all();

        $first = $snapshot();
        Product::query()->delete(); // cascades to variants, cupping scores and note links
        Origin::query()->delete();
        $this->seed(CatalogSeeder::class);

        $this->assertSame($first, $snapshot());
    }
}
