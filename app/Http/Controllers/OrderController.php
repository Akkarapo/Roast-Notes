<?php

namespace App\Http\Controllers;

use App\Actions\CancelOrder;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        return view('orders.index', [
            'orders' => $request->user()->orders()->withSum('items', 'quantity')->latest('placed_at')->latest('id')->paginate(15),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        return view('orders.show', ['order' => $order->load(['items.variant.product', 'items.review', 'discounts', 'statusChanges'])]);
    }

    public function cancel(Order $order, CancelOrder $cancelOrder): RedirectResponse
    {
        $this->authorize('cancel', $order);
        $cancelOrder($order);

        return to_route('orders.show', $order)->with('flash', __('Order cancelled. Stock has been returned.'));
    }
}
