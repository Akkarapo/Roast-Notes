<?php

namespace Tests\Feature\Admin;

use App\Enums\OrderStatus;
use App\Enums\PromotionType;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromotionAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $bean;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->bean = Product::factory()->withVariants(400, stock: 20)->create();
    }

    private function coupon(array $override = []): array
    {
        return $override + [
            'type' => 'coupon', 'name' => 'ฉลองเปิดร้าน', 'code' => 'open20', 'percent_off' => 20,
            'starts_at' => now()->subHour()->format('Y-m-d\TH:i'), 'ends_at' => '', 'is_active' => '1', 'products' => [$this->bean->id],
        ];
    }

    public function test_create_coupon_and_use_it_in_the_cart_right_away(): void
    {
        $this->actingAs($this->admin)->get('/admin/promotions/create?type=coupon')->assertOk()->assertSee($this->bean->name);
        $this->actingAs($this->admin)->post('/admin/promotions', $this->coupon())->assertSessionHasNoErrors()->assertRedirect('/admin/promotions');

        $promotion = Promotion::sole();
        $this->assertSame(PromotionType::Coupon, $promotion->type);
        $this->assertSame(['OPEN20', 20], [$promotion->coupon->code, $promotion->coupon->percent_off]);
        $this->assertNull($promotion->buyXGetY);

        $member = User::factory()->create();
        $member->cartOrCreate()->items()->create(['product_variant_id' => $this->bean->variants->first()->id, 'grind' => 'whole_bean', 'quantity' => 1]);
        $this->actingAs($member)->post('/cart/coupon', ['code' => 'OPEN20'])->assertSessionHasNoErrors();
        $this->actingAs($member)->get('/cart')->assertSee('OPEN20 ลด 20%');

        $this->actingAs($this->admin)->post("/admin/promotions/{$promotion->id}/toggle");
        $this->assertFalse($promotion->fresh()->is_active);
        $this->actingAs($member)->get('/cart')->assertDontSee('−฿80');
    }

    public function test_create_buy_x_get_y_goes_into_its_subtype_table(): void
    {
        $this->actingAs($this->admin)->post('/admin/promotions', [
            'type' => 'buy_x_get_y', 'name' => 'ซื้อ 5 แถม 1', 'buy_quantity' => 5, 'free_quantity' => 1,
            'starts_at' => now()->format('Y-m-d\TH:i'), 'is_active' => '1', 'products' => [$this->bean->id],
        ])->assertSessionHasNoErrors();

        $promotion = Promotion::sole();
        $this->assertSame([5, 1], [$promotion->buyXGetY->buy_quantity, $promotion->buyXGetY->free_quantity]);
        $this->assertNull($promotion->coupon);
        $this->actingAs($this->admin)->get('/admin/promotions')->assertSee('ซื้อ 5 แถม 1')->assertSee('data-state="active"', false);
    }

    public function test_validation(): void
    {
        $this->actingAs($this->admin)->post('/admin/promotions', $this->coupon());
        $post = fn ($o) => $this->actingAs($this->admin)->post('/admin/promotions', $this->coupon($o));

        $post(['code' => 'OPEN20'])->assertSessionHasErrors('code');            // duplicate
        $post(['code' => 'ลดราคา'])->assertSessionHasErrors('code');            // A–Z 0–9 only
        $post(['percent_off' => 0])->assertSessionHasErrors('percent_off');
        $post(['products' => []])->assertSessionHasErrors('products');
        $post(['ends_at' => now()->subDay()->format('Y-m-d\TH:i')])->assertSessionHasErrors('ends_at');
        $this->assertSame(1, Promotion::count());
    }

    public function test_type_cannot_change_on_edit(): void
    {
        $promotion = Promotion::factory()->coupon('KEEP10', 10)->forProducts([$this->bean])->create();

        $this->actingAs($this->admin)->put("/admin/promotions/{$promotion->id}", $this->coupon(['type' => 'buy_x_get_y', 'code' => 'KEEP10']))
            ->assertSessionHasErrors('type');
        $this->actingAs($this->admin)->put("/admin/promotions/{$promotion->id}", array_diff_key($this->coupon(['code' => 'KEEP10', 'percent_off' => 12]), ['type' => 1]))
            ->assertSessionHasNoErrors();
        $this->assertSame(12, $promotion->coupon->fresh()->percent_off);
    }

    public function test_only_unused_promotions_can_be_deleted(): void
    {
        $unused = Promotion::factory()->coupon('UNUSED')->forProducts([$this->bean])->create();
        $used = Promotion::factory()->coupon('USED')->forProducts([$this->bean])->create();
        OrderDiscount::factory()->for(Order::factory()->status(OrderStatus::Delivered))->create(['promotion_id' => $used->id]);

        $this->actingAs($this->admin)->delete("/admin/promotions/{$used->id}")->assertSessionHasErrors('promotion');
        $this->assertModelExists($used);
        $this->actingAs($this->admin)->get("/admin/promotions/{$used->id}/edit")->assertDontSee('data-delete', false);

        $this->actingAs($this->admin)->delete("/admin/promotions/{$unused->id}")->assertRedirect('/admin/promotions');
        $this->assertModelMissing($unused);
        $this->assertDatabaseMissing('coupons', ['promotion_id' => $unused->id]);
        $this->assertDatabaseMissing('promotion_products', ['promotion_id' => $unused->id]);
    }

    public function test_list_shows_state_and_usage(): void
    {
        Promotion::factory()->coupon('LATER')->forProducts([$this->bean])->create(['starts_at' => now()->addWeek()]);
        Promotion::factory()->coupon('OLD')->forProducts([$this->bean])->expired()->create();
        Promotion::factory()->coupon('OFF')->forProducts([$this->bean])->create(['is_active' => false]);

        $this->actingAs($this->admin)->get('/admin/promotions')
            ->assertSee('data-state="scheduled"', false)->assertSee('data-state="expired"', false)->assertSee('data-state="off"', false);
    }
}
