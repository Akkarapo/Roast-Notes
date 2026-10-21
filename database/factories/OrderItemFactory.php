<?php

namespace Database\Factories;

use App\Enums\Grind;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_variant_id' => ProductVariant::factory(),
            'product_name' => 'Test Bean',
            'size' => '250g',
            'grind' => Grind::WholeBean,
            'unit_price' => 250,
            'quantity' => 2,
            'line_total' => 500,
        ];
    }
}
