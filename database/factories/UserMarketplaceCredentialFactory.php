<?php

namespace Database\Factories;

use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserMarketplaceCredential>
 */
class UserMarketplaceCredentialFactory extends Factory
{
    protected $model = UserMarketplaceCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'marketplace_id' => Marketplace::factory()->trendyol(),
            'api_key' => fake()->bothify('????########'),
            'api_secret' => fake()->bothify('????########'),
            'additional_credentials' => ['seller_id' => (string) fake()->numberBetween(100000, 999999)],
            'is_active' => true,
            'last_sync_at' => null,
        ];
    }
}
