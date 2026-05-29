<?php

namespace Database\Factories;

use App\Models\AdCampaign;
use App\Models\AdMetric;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdMetric>
 */
class AdMetricFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ad_campaign_id' => AdCampaign::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'spend' => fake()->randomFloat(2, 10, 500),
            'attributed_revenue' => fake()->randomFloat(2, 0, 2000),
            'impressions' => fake()->numberBetween(100, 10000),
            'clicks' => fake()->numberBetween(1, 500),
            'orders' => fake()->numberBetween(0, 50),
        ];
    }
}
