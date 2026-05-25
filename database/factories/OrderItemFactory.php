<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_name' => fake()->words(3, true),
            'merchant_sku' => fake()->bothify('SKU-####'),
            'barcode' => fake()->ean13(),
            'quantity' => fake()->numberBetween(1, 5),
            'price' => fake()->randomFloat(2, 10, 500),
            'currency_code' => 'TRY',
            'line_item_status' => 'Created',
        ];
    }
}
