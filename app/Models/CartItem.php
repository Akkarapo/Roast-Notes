<?php

namespace App\Models;

use App\Enums\Grind;
use App\Support\Money;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CartItem extends Model
{
    use HasFactory;

    public const MAX_QUANTITY = 20;

    protected $fillable = ['cart_id', 'product_variant_id', 'grind', 'quantity'];

    protected function casts(): array
    {
        return ['grind' => Grind::class, 'quantity' => 'integer'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /** Cart prices always follow the current variant price. */
    public function unitSatang(): int
    {
        return Money::toSatang($this->variant->price);
    }

    public function lineSatang(): int
    {
        return $this->unitSatang() * $this->quantity;
    }

    public function exceedsStock(): bool
    {
        return $this->quantity > $this->variant->stock;
    }

    public function isPurchasable(): bool
    {
        return $this->variant->product->is_active && ! $this->exceedsStock();
    }
}
