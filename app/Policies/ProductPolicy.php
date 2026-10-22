<?php

namespace App\Policies;

use App\Models\MemberTier;
use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    /**
     * Rare lots in their early-access window can only be bought by tiers with early_access.
     * Everyone else (guests too) sees the product and the date it goes on general sale.
     */
    public function purchase(?User $user, Product $product): Response
    {
        if (! $product->isExclusiveNow() || $user?->memberTier?->early_access) {
            return Response::allow();
        }

        return Response::deny(__('Only :tier members can buy this lot until :date.', [
            'tier' => MemberTier::earlyAccessName(),
            'date' => $product->exclusive_until->translatedFormat('j M Y'),
        ]));
    }
}
