<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PromotionType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Supertype of coupons and buy-X-get-Y deals (EER specialization, disjoint + total).
 */
class Promotion extends Model
{
    use HasFactory;

    protected $fillable = ['type', 'name', 'description', 'starts_at', 'ends_at', 'is_active'];

    protected function casts(): array
    {
        return [
            'type' => PromotionType::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function coupon(): HasOne
    {
        return $this->hasOne(Coupon::class);
    }

    public function buyXGetY(): HasOne
    {
        return $this->hasOne(BuyXGetY::class);
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_products');
    }

    public function orderDiscounts(): HasMany
    {
        return $this->hasMany(OrderDiscount::class);
    }

    /** Switched on and inside its date window right now. */
    public function scopeCurrentlyActive(Builder $query): void
    {
        $query->where('promotions.is_active', true)
            ->where('promotions.starts_at', '<=', now())
            ->where(fn (Builder $q) => $q->whereNull('promotions.ends_at')->orWhere('promotions.ends_at', '>=', now()));
    }

    /** active | scheduled | expired | off — for the admin list */
    public function state(): string
    {
        return match (true) {
            ! $this->is_active => 'off',
            $this->starts_at->isFuture() => 'scheduled',
            $this->ends_at !== null && $this->ends_at->isPast() => 'expired',
            default => 'active',
        };
    }

    public function scopeWithTimesUsed(Builder $query): void
    {
        $query->withCount(['orderDiscounts as times_used' => fn (Builder $q) => $q->whereHas(
            'order', fn (Builder $o) => $o->where('status', '!=', OrderStatus::Cancelled->value)
        )]);
    }

    /** Used promotions stay (order_discounts point at them) — switch them off instead. */
    public function hasBeenUsed(): bool
    {
        return $this->orderDiscounts()->exists();
    }

    public function isCurrentlyActive(): bool
    {
        return $this->is_active && $this->starts_at->lte(now()) && ($this->ends_at === null || $this->ends_at->gte(now()));
    }

    /** Times used in orders that were not cancelled (cancelling gives the use back). */
    public function timesUsed(?User $by = null): int
    {
        return $this->orderDiscounts()
            ->whereHas('order', fn (Builder $q) => $q
                ->where('status', '!=', OrderStatus::Cancelled->value)
                ->when($by, fn (Builder $o) => $o->where('user_id', $by->id)))
            ->count();
    }
}
