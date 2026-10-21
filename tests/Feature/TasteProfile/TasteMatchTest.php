<?php

namespace Tests\Feature\TasteProfile;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\TasteProfile\Concerns\BuildsTasteProfiles;
use Tests\TestCase;

class TasteMatchTest extends TestCase
{
    use BuildsTasteProfiles, RefreshDatabase;

    private User $taster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedTaxonomy();
        $this->taster = $this->taster(['fruity', 'floral']);
    }

    private function match(Product $product): ?int
    {
        return Product::whereKey($product->id)->withTasteMatch($this->taster)->first()->matchPercent();
    }

    public function test_examples_from_the_spec(): void
    {
        $all = $this->beanWith('All Fruity Floral', ['Raspberry', 'Lemon', 'Grapefruit', 'Earl grey']);
        $half = $this->beanWith('Half', ['Lemon', 'Caramel']);

        $this->assertSame(100, $this->match($all));
        $this->assertSame(50, $this->match($half));
    }

    public function test_rounding(): void
    {
        $this->assertSame(33, $this->match($this->beanWith('One of three', ['Lemon', 'Caramel', 'Hazelnut'])));
        $this->assertSame(67, $this->match($this->beanWith('Two of three', ['Lemon', 'Jasmine', 'Hazelnut'])));
        $this->assertSame(0, $this->match($this->beanWith('None', ['Caramel', 'Hazelnut'])));
    }

    public function test_sql_and_php_calculations_agree(): void
    {
        foreach ([['Lemon', 'Caramel'], ['Jasmine', 'Peach', 'Rum'], ['Hazelnut', 'Almond'], ['Mango', 'Rose', 'Honey', 'Red wine']] as $i => $notes) {
            $product = $this->beanWith("Bean $i", $notes)->load('flavorNotes.category');
            $this->assertSame($product->matchPercentFor($this->taster), $this->match($product), implode(',', $notes));
        }
    }

    public function test_no_taste_profile_means_no_percentage(): void
    {
        $product = $this->beanWith('Any', ['Lemon'])->load('flavorNotes.category');

        $this->assertNull(Product::whereKey($product->id)->first()->matchPercent());
        $this->assertNull($product->matchPercentFor(User::factory()->create()));
    }
}
