<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    /**
     * Cancel an order and put its stock back.
     *
     * @throws ValidationException when the order has moved past the allowed status
     */
    public function __invoke(Order $order, bool $byMember = true): Order
    {
        return DB::transaction(function () use ($order, $byMember) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $allowed = $byMember ? $order->status->isCancellableByMember() : $order->status->canTransitionTo(OrderStatus::Cancelled);
            if (! $allowed) {
                throw ValidationException::withMessages(['order' => __('This order can no longer be cancelled.')]);
            }

            foreach ($order->items as $item) {
                ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
            }

            $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);

            return $order;
        });
    }
}
