<?php

namespace App\Actions\Admin;

use App\Enums\BagSize;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaveProduct
{
    /**
     * Create or update a product, its flavor notes and its bag sizes in one transaction.
     * A size that has been ordered cannot be removed (order_items point at it).
     *
     * @param  array<string, array{price: string, stock: int}>  $variants  enabled sizes keyed by size value
     * @param  list<int>  $flavorNoteIds
     */
    public function __invoke(Product $product, array $attributes, array $flavorNoteIds, array $variants): Product
    {
        return DB::transaction(function () use ($product, $attributes, $flavorNoteIds, $variants) {
            $product->fill($attributes)->save();
            $product->flavorNotes()->sync($flavorNoteIds);

            foreach ($product->variants()->get() as $existing) {
                if (! isset($variants[$existing->size->value])) {
                    if ($existing->hasBeenOrdered()) {
                        throw ValidationException::withMessages(['variants' => __(':size has been ordered and cannot be removed — set its stock to 0 instead.', ['size' => $existing->size->label()])]);
                    }
                    $existing->delete();
                }
            }

            foreach ($variants as $size => $data) {
                $size = BagSize::from($size);
                $variant = ProductVariant::firstOrNew(['product_id' => $product->id, 'size' => $size->value]);
                $variant->fill(['weight_g' => $size->grams(), 'price' => $data['price'], 'stock' => $data['stock']]);
                $variant->sku ??= strtoupper("RN-P{$product->id}-".($size === BagSize::Sachet ? 'DRIP' : $size->value));
                $variant->save();
            }

            return $product->refresh();
        });
    }
}
