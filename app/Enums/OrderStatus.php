<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case InTransit = 'in_transit';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** Normal flow, in order. Cancelled is a side exit. */
    public const FLOW = [self::Pending, self::Confirmed, self::Shipped, self::InTransit, self::Delivered];

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'รอยืนยัน',
            self::Confirmed => 'ยืนยันแล้ว',
            self::Shipped => 'ส่งพัสดุแล้ว',
            self::InTransit => 'อยู่ระหว่างจัดส่ง',
            self::Delivered => 'ได้รับแล้ว',
            self::Cancelled => 'ยกเลิก',
        };
    }

    public function next(): ?self
    {
        $index = array_search($this, self::FLOW, true);

        return $index === false ? null : (self::FLOW[$index + 1] ?? null);
    }

    /** Admin transitions: one step forward, or cancel before the parcel leaves the shop. */
    public function canTransitionTo(self $target): bool
    {
        if ($target === self::Cancelled) {
            return in_array($this, [self::Pending, self::Confirmed], true);
        }

        return $this->next() === $target;
    }

    public function isCancellableByMember(): bool
    {
        return $this === self::Pending;
    }

    /** Reviews open once the customer has the beans. */
    public function allowsReview(): bool
    {
        return $this === self::Delivered;
    }
}
