<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\MemberTier;
use App\Models\User;
use App\Support\Money;

class RecalculateMemberTier
{
    /**
     * Move the member up to the highest tier their delivered spend reaches. Never moves down.
     */
    public function __invoke(User $user): MemberTier
    {
        $spend = Money::fromSatang(static::spendOf($user));
        $earned = MemberTier::where('min_spend', '<=', $spend)->orderByDesc('min_spend')->first();
        $current = $user->memberTier;

        if ($earned && (! $current || $earned->isAbove($current))) {
            $user->forceFill(['member_tier_id' => $earned->id])->save();
            $user->setRelation('memberTier', $earned);
        }

        return $user->memberTier;
    }

    /** Cumulative spend in satang: totals of delivered orders only. */
    public static function spendOf(User $user): int
    {
        return Money::toSatang($user->orders()->where('status', OrderStatus::Delivered)->sum('total'));
    }
}
