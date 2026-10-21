<?php

namespace App\Support\Pricing;

use App\Enums\Grind;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;

final readonly class CartLine
{
    public ProductVariant $variant;

    public Product $product;

    public Grind $grind;

    public int $quantity;

    /** satang */
    public int $unitPrice;

    /** satang */
    public int $lineTotal;

    public function __construct(public CartItem $item)
    {
        $this->variant = $item->variant;
        $this->product = $item->variant->product;
        $this->grind = $item->grind;
        $this->quantity = $item->quantity;
        $this->unitPrice = $item->unitSatang();
        $this->lineTotal = $item->lineSatang();
    }
}
