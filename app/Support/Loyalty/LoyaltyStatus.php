<?php

namespace App\Support\Loyalty;

use App\Actions\RecalculateMemberTier;
use App\Models\MemberTier;
use App\Models\User;
use App\Support\Money;

/**
 * What the member sees about their tier: current tier, spend so far, and how far to the next one. Amounts in satang.
 */
final readonly class LoyaltyStatus
{
    public function __construct(
        public MemberTier $tier,
        public int $spend,
        public ?MemberTier $next,
    ) {}

    /** Built once per request per user (navbar, cart and profile all ask). */
    public static function for(User $user): self
    {
        $cache = request()->attributes->get('loyalty_status', []);
        if (! isset($cache[$user->id])) {
            $tier = $user->memberTier ?? MemberTier::findOrFail(MemberTier::BEAN);
            $cache[$user->id] = new self($tier, RecalculateMemberTier::spendOf($user), $tier->next());
            request()->attributes->set('loyalty_status', $cache);
        }

        return $cache[$user->id];
    }

    /** Satang still needed to reach the next tier (0 at the top). */
    public function remaining(): int
    {
        return $this->next ? max(0, Money::toSatang($this->next->min_spend) - $this->spend) : 0;
    }

    /** 0–100 progress from the current tier's threshold to the next one. */
    public function progress(): int
    {
        if (! $this->next) {
            return 100;
        }
        $from = Money::toSatang($this->tier->min_spend);
        $to = Money::toSatang($this->next->min_spend);

        return (int) min(100, max(0, floor(($this->spend - $from) * 100 / max(1, $to - $from))));
    }
}
