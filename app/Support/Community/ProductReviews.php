<?php

namespace App\Support\Community;

use App\Enums\BrewMethod;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Everything the product page shows about reviews: overall average, star distribution,
 * average per brew method, and the (optionally brew-filtered) list.
 */
final class ProductReviews
{
    public const PER_PAGE = 10;

    private ?Collection $byBrew = null;

    public function __construct(public readonly Product $product, public readonly ?BrewMethod $brew = null) {}

    private function query(): Builder
    {
        return Review::query()->forProduct($this->product);
    }

    /** @return array{avg: ?float, count: int, stars: array<int, int>} stars: 5 => n … 1 => n */
    public function summary(): array
    {
        $rows = $this->query()->toBase()->selectRaw('rating, COUNT(*) AS n')->groupBy('rating')->pluck('n', 'rating');
        $count = (int) $rows->sum();
        $stars = [];
        foreach ([5, 4, 3, 2, 1] as $star) {
            $stars[$star] = (int) ($rows[$star] ?? 0);
        }

        return [
            'avg' => $count ? round($rows->map(fn ($n, $rating) => $n * $rating)->sum() / $count, 1) : null,
            'count' => $count,
            'stars' => $stars,
        ];
    }

    /** @return Collection<int, object{method: BrewMethod, avg: float, count: int}> most-reviewed method first */
    public function byBrewMethod(): Collection
    {
        return $this->byBrew ??= $this->query()->toBase()
            ->selectRaw('brew_method, AVG(rating) AS avg_rating, COUNT(*) AS n')
            ->groupBy('brew_method')
            ->orderByDesc('n')->orderByDesc('avg_rating')
            ->get()
            ->map(fn ($row) => (object) ['method' => BrewMethod::from($row->brew_method), 'avg' => round((float) $row->avg_rating, 1), 'count' => (int) $row->n]);
    }

    public function list(): LengthAwarePaginator
    {
        return $this->query()
            ->when($this->brew, fn (Builder $q) => $q->where('brew_method', $this->brew->value))
            ->with('orderItem.order.user')
            ->latest()->latest('id')
            ->paginate(self::PER_PAGE, pageName: 'reviews_page')
            ->withQueryString()
            ->fragment('reviews');
    }

    /** A delivered line of this product the member has not reviewed yet (for the "Review this lot" button). */
    public function reviewableItemFor(?User $user): ?OrderItem
    {
        if (! $user) {
            return null;
        }

        return OrderItem::query()
            ->whereHas('order', fn (Builder $q) => $q->where('user_id', $user->id)->where('status', OrderStatus::Delivered))
            ->whereHas('variant', fn (Builder $q) => $q->where('product_id', $this->product->id))
            ->doesntHave('review')
            ->latest('id')
            ->first();
    }
}
