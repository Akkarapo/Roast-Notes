<?php

namespace App\Actions\Admin;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdvanceOrderStatus
{
    /**
     * Move an order one step forward (pending → confirmed → shipped → in transit → delivered) and record it.
     * $to is what the admin saw on screen, so a double click or a second tab cannot skip a step.
     *
     * @throws ValidationException when $to is not the next step from the current status
     */
    public function __invoke(Order $order, OrderStatus $to, User $admin, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $to, $admin, $note) {
            $order = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($to === OrderStatus::Cancelled || ! $order->status->canTransitionTo($to)) {
                throw ValidationException::withMessages(['status' => __('This order cannot move to :status.', ['status' => $to->label()])]);
            }

            if ($to === OrderStatus::Delivered) {
                $order->markDelivered($admin, $note); // also moves the member up a tier
            } else {
                $from = $order->status;
                $order->update(['status' => $to]);
                $order->recordStatus($from, $to, $admin, $note);
            }

            return $order;
        });
    }
}
