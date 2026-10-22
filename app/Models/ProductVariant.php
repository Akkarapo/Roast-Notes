<?php

namespace App\Models;

use App\Enums\BagSize;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'size', 'weight_g', 'price', 'stock', 'sku'];

    protected function casts(): array
    {
        return [
            'size' => BagSize::class,
            'price' => 'decimal:2',
            'stock' => 'integer',
            'weight_g' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** "Guji-Hambela Washed · 250g" */
    public function displayName(): string
    {
        return $this->product->name.' · '.$this->size->label();
    }

    public function hasBeenOrdered(): bool
    {
        return OrderItem::where('product_variant_id', $this->id)->exists();
    }

    public function inStock(): bool
    {
        return $this->stock > 0;
    }
}
