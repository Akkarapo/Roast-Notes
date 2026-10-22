<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Loyalty tier: Bean → Barista → Roaster Master, reached by cumulative spend on delivered orders.
 */
class MemberTier extends Model
{
    /** id of the entry tier (also the DB default of users.member_tier_id) */
    public const BEAN = 1;

    public $incrementing = false;

    protected $fillable = ['id', 'slug', 'name', 'min_spend', 'discount_percent', 'early_access'];

    protected function casts(): array
    {
        return [
            'min_spend' => 'decimal:2',
            'discount_percent' => 'integer',
            'early_access' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('min_spend');
    }

    /** The next tier up, or null at the top. */
    public function next(): ?self
    {
        return static::where('min_spend', '>', $this->min_spend)->ordered()->first();
    }

    /** Name of the (lowest) tier that gets early access to rare lots, for labels. */
    public static function earlyAccessName(): string
    {
        return once(fn () => static::where('early_access', true)->ordered()->value('name') ?? 'Roaster Master');
    }

    public function isAbove(self $other): bool
    {
        return (float) $this->min_spend > (float) $other->min_spend;
    }
}
