<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrderPolicy
{
    /** Someone else's order answers 404 so order numbers cannot be probed. */
    public function view(User $user, Order $order): Response
    {
        return $order->user_id === $user->id ? Response::allow() : Response::denyAsNotFound();
    }

    public function cancel(User $user, Order $order): Response
    {
        return $this->view($user, $order);
    }
}
