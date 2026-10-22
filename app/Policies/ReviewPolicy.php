<?php

namespace App\Policies;

use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ReviewPolicy
{
    /**
     * Review one purchased line: own order (others answer 404), delivered, not reviewed yet.
     */
    public function create(User $user, OrderItem $item): Response
    {
        if ($item->order->user_id !== $user->id) {
            return Response::denyAsNotFound();
        }
        if ($item->order->status !== OrderStatus::Delivered) {
            return Response::deny(__('You can review once the order has been delivered.'));
        }
        if ($item->review()->exists()) {
            return Response::deny(__('You have already reviewed this item.'));
        }

        return Response::allow();
    }

    public function update(User $user, Review $review): Response
    {
        return $review->orderItem->order->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function delete(User $user, Review $review): Response
    {
        return $this->update($user, $review);
    }
}
