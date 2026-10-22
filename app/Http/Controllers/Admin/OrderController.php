<?php

namespace App\Http\Controllers\Admin;

use App\Actions\Admin\AdvanceOrderStatus;
use App\Actions\CancelOrder;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom((string) $request->query('status'));
        $search = trim((string) $request->query('q'));

        $orders = Order::query()
            ->with('user')
            ->withSum('items', 'quantity')
            ->when($status, fn (Builder $q) => $q->where('status', $status))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('order_number', 'like', "%{$search}%")
                ->orWhere('recipient_name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhereHas('user', fn (Builder $u) => $u->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->latest('placed_at')->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'status' => $status,
            'search' => $search,
            'counts' => Order::toBase()->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', [
            'order' => $order->load(['user.memberTier', 'items', 'discounts', 'statusChanges.changedBy']),
        ]);
    }

    public function advance(Request $request, Order $order, AdvanceOrderStatus $advance): RedirectResponse
    {
        $data = $request->validate([
            'to' => ['required', Rule::enum(OrderStatus::class)],
            'note' => ['nullable', 'string', 'max:255'],
        ]);
        $to = OrderStatus::from($data['to']);
        $advance($order, $to, $request->user(), $data['note'] ?? null);

        return to_route('admin.orders.show', $order)->with('flash', __('Order :number is now :status.', ['number' => $order->order_number, 'status' => $to->label()]));
    }

    public function cancel(Request $request, Order $order, CancelOrder $cancel): RedirectResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $cancel($order, byMember: false, by: $request->user(), note: $data['reason']);

        return to_route('admin.orders.show', $order)->with('flash', __('Order cancelled. Stock has been returned.'));
    }
}
