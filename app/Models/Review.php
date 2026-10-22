<?php

namespace App\Models;

use App\Enums\BrewMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A rating + brew method (+ optional comment) for one purchased order line.
 * Who wrote it and which product it is about are reached through order_item (no duplicated columns).
 */
class Review extends Model
{
    use HasFactory;

    public const COMMENT_MAX = 1000;

    protected $fillable = ['rating', 'brew_method', 'comment'];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'brew_method' => BrewMethod::class,
        ];
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    /** Reviews of one product: reviews → order_items → product_variants.product_id */
    public function scopeForProduct(Builder $query, Product|int $product): void
    {
        $id = $product instanceof Product ? $product->id : $product;
        $query->whereHas('orderItem.variant', fn (Builder $q) => $q->where('product_id', $id));
    }

    /** Reviews written by one member: reviews → order_items → orders.user_id */
    public function scopeByUser(Builder $query, User|int $user): void
    {
        $id = $user instanceof User ? $user->id : $user;
        $query->whereHas('orderItem.order', fn (Builder $q) => $q->where('user_id', $id));
    }

    public function author(): ?User
    {
        return $this->orderItem?->order?->user;
    }

    public function wasEdited(): bool
    {
        return $this->updated_at !== null && $this->created_at !== null && $this->updated_at->gt($this->created_at);
    }
}
