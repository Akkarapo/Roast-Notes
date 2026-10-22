<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $sales = fn ($from) => Order::where('status', '!=', OrderStatus::Cancelled)->where('placed_at', '>=', $from);
        $lowStock = ProductVariant::query()
            ->where('stock', '<=', ProductController::LOW_STOCK)
            ->whereHas('product', fn (Builder $q) => $q->where('is_active', true));

        return view('admin.index', [
            'today' => ['total' => Money::toSatang($sales(today())->sum('total')), 'count' => $sales(today())->count()],
            'month' => ['total' => Money::toSatang($sales(today()->startOfMonth())->sum('total')), 'count' => $sales(today()->startOfMonth())->count()],
            'pending' => Order::where('status', OrderStatus::Pending)->count(),
            'lowStockCount' => (clone $lowStock)->count(),
            'newMembers' => User::where('role', UserRole::Member)->where('created_at', '>=', today()->startOfMonth())->count(),
            'recent' => Order::with('user')->withSum('items', 'quantity')->latest('placed_at')->latest('id')->take(8)->get(),
            'lowStock' => $lowStock->with('product')->orderBy('stock')->orderBy('id')->take(10)->get(),
        ]);
    }
}
