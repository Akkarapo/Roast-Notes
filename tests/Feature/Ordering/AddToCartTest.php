<?php

namespace Tests\Feature\Ordering;

use App\Enums\BagSize;
use App\Enums\Grind;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddToCartTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private Product $bean;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $this->bean = Product::factory()->withVariants(230, stock: 10)->create(['slug' => 'chiang-rai']);
    }

    private function variant(BagSize $size = BagSize::G250)
    {
        return $this->bean->variants->firstWhere('size', $size);
    }

    private function add(array $data)
    {
        return $this->actingAs($this->member)->from('/products/chiang-rai')->post('/cart/items', $data);
    }

    public function test_guest_is_sent_to_login_and_back_to_the_product(): void
    {
        $this->from('/products/chiang-rai')->post('/cart/items', ['variant_id' => $this->variant()->id, 'quantity' => 1])
            ->assertRedirect('/login');

        $this->post('/login', ['email' => $this->member->email, 'password' => 'password'])
            ->assertRedirect('/products/chiang-rai');
    }

    public function test_member_adds_size_grind_and_quantity(): void
    {
        $this->add(['variant_id' => $this->variant()->id, 'grind' => 'drip', 'quantity' => 2])
            ->assertRedirect('/products/chiang-rai')
            ->assertSessionHas('cart_added');

        $item = $this->member->cart->items->sole();
        $this->assertSame(2, $item->quantity);
        $this->assertSame(Grind::Drip, $item->grind);
    }

    public function test_same_variant_and_grind_adds_up_on_one_line(): void
    {
        $this->add(['variant_id' => $this->variant()->id, 'grind' => 'espresso', 'quantity' => 2]);
        $this->add(['variant_id' => $this->variant()->id, 'grind' => 'espresso', 'quantity' => 3]);
        $this->add(['variant_id' => $this->variant()->id, 'grind' => 'whole_bean', 'quantity' => 1]);

        $items = $this->member->cart->items;
        $this->assertCount(2, $items);
        $this->assertSame(5, $items->firstWhere('grind', Grind::Espresso)->quantity);
    }

    public function test_drip_bags_are_always_pre_ground(): void
    {
        $drip = Product::factory()->dripBag()->withVariants()->create();

        $this->add(['variant_id' => $drip->variants->first()->id, 'grind' => 'espresso', 'quantity' => 1]);

        $this->assertSame(Grind::None, $this->member->cart->items->sole()->grind);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $this->add(['variant_id' => $this->variant()->id, 'quantity' => 8]);
        $this->add(['variant_id' => $this->variant()->id, 'quantity' => 3])->assertSessionHasErrors('quantity');

        $this->assertSame(8, $this->member->cart->items->sole()->quantity);
    }

    public function test_quantity_must_be_between_1_and_20(): void
    {
        $this->add(['variant_id' => $this->variant()->id, 'quantity' => 0])->assertSessionHasErrors('quantity');
        $this->add(['variant_id' => $this->variant()->id, 'quantity' => 21])->assertSessionHasErrors('quantity');

        $this->assertSame(0, CartItem::count());
    }

    public function test_inactive_product_is_rejected(): void
    {
        $this->bean->update(['is_active' => false]);

        $this->add(['variant_id' => $this->variant()->id, 'quantity' => 1])->assertSessionHasErrors('variant_id');
    }

    public function test_navbar_shows_item_count(): void
    {
        $this->add(['variant_id' => $this->variant()->id, 'quantity' => 2]);
        $this->add(['variant_id' => $this->variant(BagSize::G100)->id, 'quantity' => 1]);

        $this->actingAs($this->member)->get('/')->assertSee('data-cart-count>3<', false);
    }

    public function test_product_page_has_the_add_to_cart_form(): void
    {
        $this->get('/products/chiang-rai')
            ->assertSee(route('cart.items.store'))
            ->assertSee('name="grind"', false)
            ->assertSee('บดเอสเปรสโซ');
    }
}
