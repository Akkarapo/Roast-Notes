<?php

namespace Tests\Feature\Ordering;

use App\Enums\BagSize;
use App\Enums\Grind;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private CartItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $bean = Product::factory()->withVariants(230, stock: 10)->create(['name' => 'Chiang Rai Caturra']);
        $this->item = $this->member->cartOrCreate()->items()->create([
            'product_variant_id' => $bean->variants->firstWhere('size', BagSize::G100)->id, // ฿230
            'grind' => Grind::Drip,
            'quantity' => 2,
        ]);
    }

    public function test_cart_lists_items_and_totals(): void
    {
        $this->actingAs($this->member)->get('/cart')
            ->assertOk()
            ->assertSee('Chiang Rai Caturra')
            ->assertSee('฿460')                 // subtotal
            ->assertSee('ค่าส่ง')
            ->assertSee('data-total>฿510<', false) // + ฿50 shipping
            ->assertSee('ซื้อเพิ่มอีก ฿540 ส่งฟรี')
            ->assertSee(route('checkout.create'));
    }

    public function test_member_changes_quantity_and_grind(): void
    {
        $this->actingAs($this->member)->patch("/cart/items/{$this->item->id}", ['quantity' => 4, 'grind' => 'espresso'])
            ->assertRedirect('/cart');

        $this->item->refresh();
        $this->assertSame(4, $this->item->quantity);
        $this->assertSame(Grind::Espresso, $this->item->grind);
    }

    public function test_switching_to_a_grind_already_in_the_cart_merges_lines(): void
    {
        $this->member->cart->items()->create(['product_variant_id' => $this->item->product_variant_id, 'grind' => Grind::Espresso, 'quantity' => 1]);

        $this->actingAs($this->member)->patch("/cart/items/{$this->item->id}", ['grind' => 'espresso']);

        $this->assertSame(3, $this->member->cart->items()->sole()->quantity);
    }

    public function test_quantity_cannot_exceed_stock(): void
    {
        $this->actingAs($this->member)->patch("/cart/items/{$this->item->id}", ['quantity' => 11])->assertSessionHasErrors('quantity');

        $this->assertSame(2, $this->item->fresh()->quantity);
    }

    public function test_member_removes_an_item(): void
    {
        $this->actingAs($this->member)->delete("/cart/items/{$this->item->id}")->assertRedirect('/cart');

        $this->assertModelMissing($this->item);
        $this->actingAs($this->member)->get('/cart')->assertSee('ตะกร้าของคุณยังว่างอยู่');
    }

    public function test_other_members_cannot_touch_my_cart(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)->patch("/cart/items/{$this->item->id}", ['quantity' => 5])->assertNotFound();
        $this->actingAs($stranger)->delete("/cart/items/{$this->item->id}")->assertNotFound();
        $this->assertSame(2, $this->item->fresh()->quantity);
    }

    public function test_checkout_is_blocked_when_stock_dropped_below_cart_quantity(): void
    {
        $this->item->variant->update(['stock' => 1]);

        $this->actingAs($this->member)->get('/cart')
            ->assertSee('เหลือในสต็อกเพียง 1 ชิ้น')
            ->assertDontSee(route('checkout.create'));
    }

    public function test_free_shipping_from_one_thousand_baht(): void
    {
        $this->item->update(['quantity' => 5]); // 5 × 230 = 1,150

        $this->actingAs($this->member)->get('/cart')->assertSee('ฟรี')->assertSee('data-total>฿1,150<', false);
    }
}
