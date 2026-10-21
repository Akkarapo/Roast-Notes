<?php

namespace Tests\Feature\Ordering;

use App\Enums\BagSize;
use App\Enums\Grind;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Pricing\DiscountLine;
use App\Support\Pricing\DiscountRule;
use App\Support\Pricing\PricingContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    private User $member;

    private ProductVariant $variant;

    private array $shipping = ['recipient_name' => 'สมชาย ใจดี', 'phone' => '0811111111', 'shipping_address' => '12 ถ.นิมมานเหมินท์ เชียงใหม่', 'note' => 'ไม่ต้องใส่ถุง'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create(['phone' => '0899999999', 'address' => 'ที่อยู่ในโปรไฟล์']);
        $product = Product::factory()->withVariants(230, stock: 10)->create(['name' => 'Chiang Rai Caturra']);
        $this->variant = $product->variants->firstWhere('size', BagSize::G250); // ฿460
        $this->member->cartOrCreate()->items()->create(['product_variant_id' => $this->variant->id, 'grind' => Grind::Drip, 'quantity' => 2]);
    }

    private function checkout()
    {
        return $this->actingAs($this->member)->post('/checkout', $this->shipping);
    }

    public function test_empty_cart_cannot_check_out(): void
    {
        $this->member->cart->items()->delete();

        $this->actingAs($this->member)->get('/checkout')->assertRedirect('/cart');
        $this->checkout()->assertRedirect('/cart')->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_checkout_page_prefills_shipping_from_profile_and_shows_totals(): void
    {
        $this->actingAs($this->member)->get('/checkout')
            ->assertOk()
            ->assertSee('0899999999')
            ->assertSee('ที่อยู่ในโปรไฟล์')
            ->assertSee('Chiang Rai Caturra')
            ->assertSee('data-total>฿970<', false); // 2 × 460 = 920, under ฿1,000 → + ฿50 shipping
    }

    public function test_placing_an_order_snapshots_items_takes_stock_and_empties_cart(): void
    {
        $response = $this->checkout();

        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));
        $this->assertSame(OrderStatus::Pending, $order->status);
        $this->assertMatchesRegularExpression('/^RN-\d{8}-0001$/', $order->order_number);
        $this->assertEquals(920, $order->subtotal);
        $this->assertEquals(50, $order->shipping_fee);  // 920 < 1,000
        $this->assertEquals(970, $order->total);
        $this->assertSame('สมชาย ใจดี', $order->recipient_name);

        $item = $order->items->sole();
        $this->assertSame(['Chiang Rai Caturra', '250g', Grind::Drip, 2], [$item->product_name, $item->size, $item->grind, $item->quantity]);
        $this->assertEquals(460, $item->unit_price);

        $this->assertSame(8, $this->variant->fresh()->stock);
        $this->assertCount(0, $this->member->cart->items()->get());
    }

    public function test_out_of_stock_saves_nothing_and_explains_why(): void
    {
        $this->variant->update(['stock' => 1]);

        $this->checkout()->assertRedirect('/cart')->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, $this->variant->fresh()->stock);
        $this->assertCount(1, $this->member->cart->items()->get());
    }

    public function test_hidden_product_blocks_checkout(): void
    {
        $this->variant->product->update(['is_active' => false]);

        $this->checkout()->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_later_price_changes_do_not_alter_the_order(): void
    {
        $this->checkout();
        $this->variant->update(['price' => 999]);

        $this->actingAs($this->member)->get(route('orders.show', Order::sole()))->assertSee('฿460')->assertDontSee('฿999');
    }

    public function test_order_numbers_count_up_within_a_day(): void
    {
        $this->checkout();
        $this->member->cart->items()->create(['product_variant_id' => $this->variant->id, 'grind' => Grind::Drip, 'quantity' => 1]);
        $this->checkout();

        $this->assertSame(['0001', '0002'], Order::orderBy('id')->pluck('order_number')->map(fn ($n) => substr($n, -4))->all());
    }

    public function test_discounts_from_rules_are_recorded_on_the_order(): void
    {
        $rule = new class implements DiscountRule
        {
            public function apply(PricingContext $context): array
            {
                return [new DiscountLine('coupon', 'TEST10 ลด 10%', intdiv($context->subtotal, 10))];
            }
        };
        config(['roastnotes.discount_rules' => [$rule::class]]);
        $this->app->instance($rule::class, $rule);

        $this->checkout();

        $order = Order::sole();
        $this->assertEquals(92, $order->discount_total);
        $this->assertEquals(50, $order->shipping_fee);       // 828 after discount < 1000
        $this->assertEquals(920 - 92 + 50, $order->total);
        $this->assertSame('TEST10 ลด 10%', $order->discounts->sole()->label);
    }

    public function test_shipping_details_are_required(): void
    {
        $this->actingAs($this->member)->post('/checkout', ['recipient_name' => '', 'phone' => 'abc', 'shipping_address' => ''])
            ->assertSessionHasErrors(['recipient_name', 'phone', 'shipping_address']);
    }
}
