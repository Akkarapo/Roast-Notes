<?php

namespace Tests\Feature\Promotions;

use App\Enums\PromotionType;
use App\Models\Coupon;
use App\Models\OrderDiscount;
use App\Models\Product;
use App\Models\Promotion;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_subtypes_share_the_supertype_key(): void
    {
        $coupon = Promotion::factory()->coupon('lightroast15', 15)->create();
        $deal = Promotion::factory()->buyXGetY(10, 2)->create();

        $this->assertSame(PromotionType::Coupon, $coupon->type);
        $this->assertSame($coupon->id, $coupon->coupon->promotion_id);
        $this->assertSame('LIGHTROAST15', $coupon->coupon->code);   // stored upper-case
        $this->assertSame(12, $deal->buyXGetY->groupSize());
        $this->assertNull($deal->coupon);
    }

    public function test_coupon_code_is_unique(): void
    {
        Promotion::factory()->coupon('DUP10')->create();

        $this->expectException(QueryException::class);
        Promotion::factory()->coupon('dup10')->create();
    }

    public function test_participating_products_and_on_promotion_scope(): void
    {
        [$in, $out] = Product::factory()->count(2)->create();
        Promotion::factory()->coupon()->forProducts([$in])->create();
        Promotion::factory()->coupon('OLD')->expired()->forProducts([$out])->create();

        $this->assertSame([$in->id], Product::onPromotion()->pluck('id')->all());
    }

    public function test_deleting_a_promotion_removes_subtype_and_links_but_keeps_order_history(): void
    {
        $promotion = Promotion::factory()->coupon()->forProducts(Product::factory()->count(1)->create())->create();
        $discount = OrderDiscount::factory()->create(['promotion_id' => $promotion->id]);

        $promotion->delete();

        $this->assertSame(0, Coupon::count());
        $this->assertSame(0, DB::table('promotion_products')->count());
        $this->assertNull($discount->fresh()->promotion_id);
        $this->assertNotNull($discount->fresh());
    }
}
