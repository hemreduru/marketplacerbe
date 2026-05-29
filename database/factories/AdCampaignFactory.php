<?php

namespace Database\Factories;

use App\Models\AdCampaign;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdCampaign>
 */
class AdCampaignFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'marketplace_code' => 'trendyol',
            'remote_campaign_id' => (string) fake()->unique()->numberBetween(10000, 99999),
            'name' => fake()->words(3, true),
            'status' => 'active',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
        ];
    }
}
