<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CancelOrder
{
    /**
     * Cancel an order and put its stock back.
     *
     * @throws ValidationException when the order has moved past the allowed status
     */
    public function __invoke(Order $order, bool $byMember = true, ?User $by = null, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $byMember, $by, $note) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $allowed = $byMember ? $order->status->isCancellableByMember() : $order->status->canTransitionTo(OrderStatus::Cancelled);
            if (! $allowed) {
                throw ValidationException::withMessages(['order' => __('This order can no longer be cancelled.')]);
            }

            foreach ($order->items as $item) {
                ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
            }

            $from = $order->status;
            $order->update(['status' => OrderStatus::Cancelled, 'cancelled_at' => now()]);
            $order->recordStatus($from, OrderStatus::Cancelled, $by ?? ($byMember ? $order->user : null), $note);

            return $order;
        });
    }
}
