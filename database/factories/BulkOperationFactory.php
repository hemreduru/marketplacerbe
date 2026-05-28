<?php

namespace Database\Factories;

use App\Models\BulkOperation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BulkOperationFactory extends Factory
{
    protected $model = BulkOperation::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'operation_type' => 'price_update',
            'status' => 'pending',
            'total_items' => 0,
            'processed_items' => 0,
            'failed_items' => 0,
            'filters' => [],
            'payload' => [],
        ];
    }

    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => 'completed',
            'started_at' => now()->subMinutes(5),
            'completed_at' => now(),
            'processed_items' => $this->faker->numberBetween(10, 100),
            'total_items' => $this->faker->numberBetween(10, 100),
        ]);
    }
}
