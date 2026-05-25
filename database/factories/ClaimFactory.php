<?php

namespace Database\Factories;

use App\Models\Claim;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Claim>
 */
class ClaimFactory extends Factory
{
    protected $model = Claim::class;

    public function definition(): array
    {
        return [
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'remote_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'order_number' => (string) fake()->numberBetween(100000000, 999999999),
            'status' => 'Created',
            'customer_name' => fake()->name(),
            'item_count' => fake()->numberBetween(1, 3),
            'claim_date' => fake()->dateTimeBetween('-20 days', 'now'),
            'raw_data' => [],
        ];
    }
}
