<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\ProcessMethod;
use App\Enums\ProductType;
use App\Enums\RoastLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

class Product extends Model
{
    use HasFactory;

    public const SORTS = ['newest', 'price_asc', 'price_desc', 'score', 'rating', 'match'];

    protected $fillable = [
        'origin_id', 'name', 'slug', 'nickname', 'type', 'farm_name', 'variety', 'process',
        'altitude_m', 'roast_level', 'roast_date', 'description', 'is_active', 'cqi_ref', 'exclusive_until',
    ];

    protected function casts(): array
    {
        return [
            'type' => ProductType::class,
            'process' => ProcessMethod::class,
            'roast_level' => RoastLevel::class,
            'roast_date' => 'date',
            'is_active' => 'boolean',
            'exclusive_until' => 'datetime',
            'altitude_m' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function origin(): BelongsTo
    {
        return $this->belongsTo(Origin::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('weight_g');
    }

    public function flavorNotes(): BelongsToMany
    {
        return $this->belongsToMany(FlavorNote::class, 'product_flavor_notes');
    }

    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, ProductVariant::class, 'product_id', 'product_variant_id');
    }

    /** Units sold across all sizes, ignoring cancelled orders (adds `units_sold`). */
    public function scopeWithUnitsSold(Builder $query): void
    {
        $query->withSum(['orderItems as units_sold' => fn (Builder $q) => $q->whereHas(
            'order', fn (Builder $o) => $o->where('status', '!=', OrderStatus::Cancelled->value)
        )], 'quantity');
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_products');
    }

    /** Products in at least one promotion that is active right now. */
    public function scopeOnPromotion(Builder $query): void
    {
        $query->whereHas('promotions', fn (Builder $q) => $q->currentlyActive());
    }

    /** Members who saved this product. */
    public function wishedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists');
    }

    /** Reviews reach products through order_items → product_variants (see Review::scopeForProduct). */
    public function reviewsQuery(): Builder
    {
        return Review::query()->forProduct($this);
    }

    /**
     * rating_avg + rating_count for cards, as correlated subqueries (reviews → order_items → product_variants),
     * so a page of cards still costs one query.
     */
    public function scopeWithRating(Builder $query): void
    {
        $reviews = fn () => Review::query()
            ->join('order_items', 'order_items.id', '=', 'reviews.order_item_id')
            ->join('product_variants', 'product_variants.id', '=', 'order_items.product_variant_id')
            ->whereColumn('product_variants.product_id', 'products.id');

        if ($query->getQuery()->columns === null) {
            $query->select('products.*');
        }
        $query->addSelect([
            'rating_avg' => $reviews()->selectRaw('ROUND(AVG(reviews.rating), 1)'),
            'rating_count' => $reviews()->selectRaw('COUNT(*)'),
        ]);
    }

    public function cupping(): HasOne
    {
        return $this->hasOne(CuppingScore::class);
    }

    /** Rare lot still in its early-access window (only tiers with early_access may buy it). */
    public function isExclusiveNow(): bool
    {
        return $this->exclusive_until !== null && $this->exclusive_until->isFuture();
    }

    public function scopeExclusiveNow(Builder $query): void
    {
        $query->where('exclusive_until', '>', now());
    }

    public function isSoldOut(): bool
    {
        return $this->variants->every(fn (ProductVariant $variant) => ! $variant->inStock());
    }

    // ---- Taste match: "เข้ากับลิ้นคุณ X%" ----

    /**
     * Adds notes_total and notes_liked counts: a note is "liked" when its top-level
     * category is one the member picked in the Taste Quiz.
     */
    public function scopeWithTasteMatch(Builder $query, User $user): void
    {
        $liked = $user->likedCategoryIds();

        $query->withCount([
            'flavorNotes as notes_total',
            'flavorNotes as notes_liked' => fn (Builder $q) => $q->whereHas(
                'category', fn (Builder $c) => $c->whereIn('parent_id', $liked)
            ),
        ]);
    }

    /** Best match first, then the member's favourite roast, then freshest. Needs withTasteMatch(). */
    public function scopeSortedByMatch(Builder $query, User $user): void
    {
        $query->orderByRaw('notes_liked / NULLIF(notes_total, 0) DESC');
        if ($user->preferred_roast_level) {
            $query->orderByRaw('roast_level = ? DESC', [$user->preferred_roast_level->value]);
        }
        $query->orderByDesc('roast_date')->orderBy('products.id');
    }

    /** Match % from withTasteMatch() counts; null when the scope was not applied. */
    public function matchPercent(): ?int
    {
        $total = $this->getAttribute('notes_total');

        return $total ? (int) round($this->getAttribute('notes_liked') / $total * 100) : null;
    }

    /** Same number computed from loaded notes (detail page). Needs flavorNotes.category. */
    public function matchPercentFor(User $user): ?int
    {
        if (! $user->hasTasteProfile() || $this->flavorNotes->isEmpty()) {
            return null;
        }

        return (int) round($this->likedNotesFor($user)->count() / $this->flavorNotes->count() * 100);
    }

    /** @return Collection<int, FlavorNote> */
    public function likedNotesFor(User $user)
    {
        $liked = $user->likedCategoryIds();

        return $this->flavorNotes->filter(fn (FlavorNote $note) => $liked->contains($note->category->parent_id))->values();
    }

    // ---- Query scopes (reused by taste-profile and admin) ----

    public function scopeActive(Builder $query): void
    {
        $query->where('products.is_active', true);
    }

    /** @param  list<string>  $slugs  top-level flavor category slugs, OR-ed together */
    public function scopeWithFlavorCategories(Builder $query, array $slugs): void
    {
        $query->whereHas('flavorNotes.category.parent', fn (Builder $q) => $q->whereIn('slug', $slugs));
    }

    /** @param  list<RoastLevel>  $levels */
    public function scopeRoastLevels(Builder $query, array $levels): void
    {
        $query->whereIn('roast_level', array_map(fn (RoastLevel $level) => $level->value, $levels));
    }

    public function scopeOfType(Builder $query, ProductType $type): void
    {
        $query->where('type', $type->value);
    }

    public function scopeFromCountry(Builder $query, string $country): void
    {
        $query->whereHas('origin', fn (Builder $q) => $q->where('country', $country));
    }

    public function scopeSorted(Builder $query, string $sort): void
    {
        match ($sort) {
            'price_asc' => $query->orderBy('variants_min_price')->orderBy('products.id'),
            'price_desc' => $query->orderByDesc('variants_min_price')->orderBy('products.id'),
            'score' => $query->orderByDesc(
                CuppingScore::select('total_points')->whereColumn('cupping_scores.product_id', 'products.id')
            )->orderBy('products.id'),
            // needs withRating(); products without reviews go last
            'rating' => $query->orderByRaw('rating_avg IS NULL')->orderByDesc('rating_avg')->orderByDesc('rating_count')->orderBy('products.id'),
            default => $query->orderByDesc('roast_date')->orderBy('products.id'),
        };
    }

    /**
     * Apply validated filters from ProductFilterRequest::filters().
     *
     * @param  array{flavor?: list<string>, roast?: list<RoastLevel>, type?: ?ProductType, country?: ?string, promo?: bool, rare?: bool}  $filters
     */
    public function scopeFilter(Builder $query, array $filters): void
    {
        $query
            ->when($filters['flavor'] ?? [], fn (Builder $q, array $slugs) => $q->withFlavorCategories($slugs))
            ->when($filters['roast'] ?? [], fn (Builder $q, array $levels) => $q->roastLevels($levels))
            ->when($filters['type'] ?? null, fn (Builder $q, ProductType $type) => $q->ofType($type))
            ->when($filters['country'] ?? null, fn (Builder $q, string $country) => $q->fromCountry($country))
            ->when($filters['promo'] ?? false, fn (Builder $q) => $q->onPromotion())
            ->when($filters['rare'] ?? false, fn (Builder $q) => $q->exclusiveNow());
    }
}
