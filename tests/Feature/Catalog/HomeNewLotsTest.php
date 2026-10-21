<?php

namespace Tests\Feature\Catalog;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeNewLotsTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_the_four_most_recently_roasted_active_products(): void
    {
        foreach ([3, 9, 12, 20, 30] as $days) {
            Product::factory()->withVariants()->create(['name' => "Roasted {$days}d", 'roast_date' => now()->subDays($days)]);
        }
        Product::factory()->withVariants()->inactive()->create(['name' => 'Hidden Fresh', 'roast_date' => now()->subDay()]);

        $response = $this->get('/')->assertOk();

        $this->assertSame(['Roasted 3d', 'Roasted 9d', 'Roasted 12d', 'Roasted 20d'], $response->viewData('newLots')->pluck('name')->all());
        $response->assertDontSee('Hidden Fresh')->assertSee(route('products.index', ['sort' => 'newest']));
    }

    public function test_home_shows_empty_state_without_products(): void
    {
        $this->get('/')->assertOk()->assertSee('ยังไม่มีเมล็ดกาแฟ');
    }
}
