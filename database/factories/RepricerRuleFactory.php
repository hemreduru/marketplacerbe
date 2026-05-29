<?php

namespace Database\Factories;

use App\Models\RepricerRule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RepricerRule>
 */
class RepricerRuleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'master_product_id' => null,
            'name' => fake()->words(2, true),
            'strategy' => 'fixed',
            'min_price' => 10,
            'max_price' => 1000,
            'target_margin' => null,
            'undercut_amount' => null,
            'is_active' => true,
        ];
    }
}
