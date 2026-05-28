<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentEvent;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShipmentEventFactory extends Factory
{
    protected $model = ShipmentEvent::class;

    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'status' => $this->faker->randomElement(['in_transit', 'out_for_delivery', 'delivered']),
            'location' => $this->faker->city(),
            'description' => $this->faker->sentence(),
            'occurred_at' => now(),
            'source' => 'polling',
            'external_reference' => null,
        ];
    }
}
