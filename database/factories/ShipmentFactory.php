<?php

namespace Database\Factories;

use App\Models\CargoProvider;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'user_id' => User::factory(),
            'cargo_provider_id' => CargoProvider::factory()->yurtici(),
            'tracking_number' => 'YT'.$this->faker->unique()->numerify('#######'),
            'label_format' => 'zpl',
            'status' => 'created',
            'package_count' => 1,
            'total_weight_kg' => $this->faker->randomFloat(3, 0.1, 30),
            'total_desi' => $this->faker->randomFloat(3, 1, 50),
            'sender_address' => [],
            'receiver_address' => [],
        ];
    }

    public function shipped(): static
    {
        return $this->state(fn () => [
            'status' => 'in_transit',
            'shipped_at' => now(),
        ]);
    }

    public function delivered(): static
    {
        return $this->state(fn () => [
            'status' => 'delivered',
            'shipped_at' => now()->subDays(2),
            'delivered_at' => now(),
        ]);
    }
}
