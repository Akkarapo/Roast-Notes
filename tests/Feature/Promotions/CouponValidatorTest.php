<?php

namespace Tests\Feature\Promotions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderDiscount;
use App\Models\Promotion;
use App\Models\User;
use App\Support\Promotions\CouponValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Promotions\Concerns\BuildsCarts;
use Tests\TestCase;

class CouponValidatorTest extends TestCase
{
    use BuildsCarts, RefreshDatabase;

    private $bean;

    protected function setUp(): void
    {
        parent::setUp();
        $this->member = User::factory()->create();
        $this->bean = $this->bean(230);
        $this->add($this->bean, 1);
    }

    private function reason(string $code): ?string
    {
        return app(CouponValidator::class)->check($code, $this->member, $this->quote()->lines)->reason;
    }

    private function useOnce(Promotion $promotion, ?User $by = null, OrderStatus $status = OrderStatus::Delivered): void
    {
        OrderDiscount::factory()->for(Order::factory()->for($by ?? User::factory()->create())->status($status))
            ->create(['promotion_id' => $promotion->id]);
    }

    public function test_valid_coupon_in_any_case(): void
    {
        Promotion::factory()->coupon('GOOD10')->forProducts([$this->bean])->create();

        $this->assertNull($this->reason(' good10 '));
    }

    public function test_unknown_or_switched_off(): void
    {
        Promotion::factory()->coupon('OFF10')->forProducts([$this->bean])->create(['is_active' => false]);

        $this->assertSame('ไม่พบคูปองนี้', $this->reason('NOPE'));
        $this->assertSame('ไม่พบคูปองนี้', $this->reason('OFF10'));
    }

    public function test_not_started_and_expired(): void
    {
        Promotion::factory()->coupon('SOON')->forProducts([$this->bean])->create(['starts_at' => now()->addDay()]);
        Promotion::factory()->coupon('SUMMER20')->expired()->forProducts([$this->bean])->create();

        $this->assertSame('คูปองนี้ยังไม่เริ่มใช้', $this->reason('SOON'));
        $this->assertSame('คูปองนี้หมดอายุแล้ว', $this->reason('SUMMER20'));
    }

    public function test_total_usage_limit_counts_only_live_orders(): void
    {
        $promotion = Promotion::factory()->coupon('TWO', 10, ['usage_limit' => 2])->forProducts([$this->bean])->create();
        $this->useOnce($promotion);
        $this->useOnce($promotion, status: OrderStatus::Cancelled);
        $this->assertNull($this->reason('TWO'));

        $this->useOnce($promotion);
        $this->assertSame('คูปองนี้ถูกใช้ครบแล้ว', $this->reason('TWO'));
    }

    public function test_per_member_limit_and_cancelling_gives_it_back(): void
    {
        $promotion = Promotion::factory()->coupon('ONCE', 10, ['per_user_limit' => 1])->forProducts([$this->bean])->create();
        $this->useOnce($promotion, $this->member);
        $this->assertSame('คุณใช้คูปองนี้ครบสิทธิ์แล้ว', $this->reason('ONCE'));

        Order::where('user_id', $this->member->id)->update(['status' => OrderStatus::Cancelled]);
        $this->assertNull($this->reason('ONCE'));
    }

    public function test_needs_participating_products_and_minimum(): void
    {
        $other = $this->bean(100);
        Promotion::factory()->coupon('ELSE')->forProducts([$other])->create();
        Promotion::factory()->coupon('ROASTER50', 10, ['min_subtotal' => 500])->forProducts([$this->bean])->create();

        $this->assertSame('ยังไม่มีสินค้าที่ร่วมรายการในตะกร้า', $this->reason('ELSE'));
        $this->assertSame('ซื้อสินค้าร่วมรายการเพิ่มอีก ฿270 เพื่อใช้คูปอง', $this->reason('ROASTER50'));
    }
}
