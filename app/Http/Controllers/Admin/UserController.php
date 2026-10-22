<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\MemberTier;
use App\Models\Review;
use App\Models\User;
use App\Support\Loyalty\LoyaltyStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $role = UserRole::tryFrom((string) $request->query('role'));
        $tier = MemberTier::where('slug', (string) $request->query('tier'))->first();
        $search = trim((string) $request->query('q'));

        $users = User::query()
            ->with('memberTier')
            ->withCount('orders')
            ->withSum(['orders as spend' => fn (Builder $q) => $q->where('status', OrderStatus::Delivered)], 'total')
            ->when($role, fn (Builder $q) => $q->where('role', $role))
            ->when($tier, fn (Builder $q) => $q->where('member_tier_id', $tier->id))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'role' => $role,
            'tier' => $tier,
            'search' => $search,
            'tiers' => MemberTier::ordered()->get(),
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->load(['memberTier', 'tastePreferences', 'wishlist']),
            'loyalty' => LoyaltyStatus::for($user),
            'orders' => $user->orders()->withSum('items', 'quantity')->latest('placed_at')->latest('id')->get(),
            'reviews' => Review::byUser($user)->with('orderItem')->latest()->get(),
            'tiers' => MemberTier::ordered()->get(),
        ]);
    }

    /** Manual override. Delivered orders can still move the member up later (tiers only go up automatically). */
    public function updateTier(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['member_tier_id' => ['required', Rule::exists(MemberTier::class, 'id')]]);
        $user->forceFill(['member_tier_id' => $data['member_tier_id']])->save();

        return back()->with('flash', __(':name is now :tier.', ['name' => $user->name, 'tier' => $user->fresh()->memberTier->name]));
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $role = UserRole::from($request->validate(['role' => ['required', Rule::enum(UserRole::class)]])['role']);

        if ($role === UserRole::Member && $user->isAdmin()) {
            if ($user->is($request->user())) {
                throw ValidationException::withMessages(['role' => __('You cannot remove your own admin rights.')]);
            }
            if (User::where('role', UserRole::Admin)->count() <= 1) {
                throw ValidationException::withMessages(['role' => __('There must be at least one admin.')]);
            }
        }
        $user->forceFill(['role' => $role])->save();

        return back()->with('flash', __(':name is now :role.', ['name' => $user->name, 'role' => $role->label()]));
    }
}
