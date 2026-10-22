<?php

namespace Tests\Feature\Foundation;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The owner's "Bean" mascot shows up where the UI needs a friendly face, and the tab icon is the bean. */
class MascotTest extends TestCase
{
    use RefreshDatabase;

    public function test_tab_icon_and_hero(): void
    {
        $this->get('/')->assertSee('href="/favicon-32.png"', false)->assertSee('data-mascot="pour"', false)->assertSee('images/mascot/pour.png');
        $this->get('/login')->assertSee('href="/favicon-32.png"', false)->assertSee('data-mascot="wave"', false);
        foreach (['pour', 'wave', 'sip', 'search', 'sleep', 'nap', 'bag', 'cheer', 'delivery', 'stir', 'peek', 'carry', 'takeaway', 'bag-up'] as $pose) {
            $this->assertFileExists(public_path("images/mascot/{$pose}.png"));
        }
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
    }

    public function test_empty_states_404_and_order_placed(): void
    {
        $member = User::factory()->create();
        $this->actingAs($member)->get('/cart')->assertSee('data-mascot="sleep"', false);
        $this->actingAs($member)->get('/wishlist')->assertSee('data-mascot="search"', false);
        $this->get('/products/does-not-exist')->assertNotFound()->assertSee('data-mascot="search"', false)->assertSee('หาหน้านี้ไม่เจอ');

        $member->cartOrCreate()->items()->create(['product_variant_id' => Product::factory()->withVariants()->create()->variants->first()->id, 'grind' => 'whole_bean', 'quantity' => 1]);
        $this->actingAs($member)->followingRedirects()
            ->post('/checkout', ['recipient_name' => 'ก', 'phone' => '0800000000', 'shipping_address' => 'ข'])
            ->assertSee('data-order-placed', false)->assertSee('data-mascot="cheer"', false);
    }
}
