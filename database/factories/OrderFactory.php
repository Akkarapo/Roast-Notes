<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_number' => 'RN-'.now()->format('Ymd').'-'.fake()->unique()->numerify('####'),
            'user_id' => User::factory(),
            'status' => OrderStatus::Pending,
            'subtotal' => 500,
            'discount_total' => 0,
            'shipping_fee' => 50,
            'total' => 550,
            'recipient_name' => fake()->name(),
            'phone' => '0812345678',
            'shipping_address' => fake()->address(),
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
