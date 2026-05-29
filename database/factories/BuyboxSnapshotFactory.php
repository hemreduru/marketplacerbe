<?php

namespace Database\Factories;

use App\Models\BuyboxSnapshot;
use App\Models\MarketplaceListing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BuyboxSnapshot>
 */
class BuyboxSnapshotFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'marketplace_listing_id' => MarketplaceListing::factory(),
            'has_buybox' => fake()->boolean(),
            'our_price' => fake()->randomFloat(2, 50, 1000),
            'competitor_price' => fake()->randomFloat(2, 50, 1000),
            'competitor_seller' => fake()->company(),
            'checked_at' => now(),
        ];
    }
}
