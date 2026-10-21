<?php

namespace Tests\Unit\Ordering;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_flow_moves_one_step_at_a_time(): void
    {
        $this->assertSame(OrderStatus::Confirmed, OrderStatus::Pending->next());
        $this->assertSame(OrderStatus::Shipped, OrderStatus::Confirmed->next());
        $this->assertSame(OrderStatus::InTransit, OrderStatus::Shipped->next());
        $this->assertSame(OrderStatus::Delivered, OrderStatus::InTransit->next());
        $this->assertNull(OrderStatus::Delivered->next());
        $this->assertNull(OrderStatus::Cancelled->next());
    }

    public function test_admin_transitions(): void
    {
        $this->assertTrue(OrderStatus::Pending->canTransitionTo(OrderStatus::Confirmed));
        $this->assertFalse(OrderStatus::Pending->canTransitionTo(OrderStatus::Shipped));
        $this->assertTrue(OrderStatus::Confirmed->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Shipped->canTransitionTo(OrderStatus::Cancelled));
        $this->assertFalse(OrderStatus::Delivered->canTransitionTo(OrderStatus::Pending));
    }

    public function test_member_can_only_cancel_pending(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertSame($status === OrderStatus::Pending, $status->isCancellableByMember(), $status->value);
        }
    }

    public function test_reviews_open_only_when_delivered(): void
    {
        foreach (OrderStatus::cases() as $status) {
            $this->assertSame($status === OrderStatus::Delivered, $status->allowsReview(), $status->value);
        }
    }

    public function test_thai_labels(): void
    {
        $this->assertSame('อยู่ระหว่างจัดส่ง', OrderStatus::InTransit->label());
        $this->assertSame('ได้รับแล้ว', OrderStatus::Delivered->label());
    }
}
