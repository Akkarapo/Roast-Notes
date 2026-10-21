<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderDiscount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderDiscount>
 */
class OrderDiscountFactory extends Factory
{
    public function definition(): array
    {
        return ['order_id' => Order::factory(), 'source' => 'coupon', 'label' => 'TEST10', 'amount' => 50];
    }
}
