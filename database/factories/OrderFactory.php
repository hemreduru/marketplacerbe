<?php

namespace Database\Factories;

use App\Models\Marketplace;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'marketplace_id' => Marketplace::factory()->trendyol(),
            'order_number' => (string) fake()->unique()->numberBetween(100000000, 999999999),
            'customer_first_name' => fake()->firstName(),
            'customer_last_name' => fake()->lastName(),
            'customer_email' => fake()->safeEmail(),
            'total_amount' => fake()->randomFloat(2, 50, 2000),
            'currency_code' => 'TRY',
            'status' => 'Created',
            'shipment_package_status' => 'Created',
            'order_date' => fake()->dateTimeBetween('-30 days', 'now'),
            'cargo_tracking_number' => null,
            'cargo_provider_name' => null,
            'raw_data' => [],
        ];
    }
}
