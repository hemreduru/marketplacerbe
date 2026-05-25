<?php

namespace Database\Factories;

use App\Models\FinancialDailySummary;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FinancialDailySummary>
 */
class FinancialDailySummaryFactory extends Factory
{
    protected $model = FinancialDailySummary::class;

    public function definition(): array
    {
        $gross = fake()->randomFloat(2, 100, 5000);
        $commission = round($gross * 0.15, 2);

        return [
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'gross_sales' => $gross,
            'commission' => $commission,
            'shipping_cost' => fake()->randomFloat(2, 0, 100),
            'platform_expense' => fake()->randomFloat(2, 0, 50),
            'other_expense' => fake()->randomFloat(2, 0, 50),
            'net_profit' => round($gross - $commission, 2),
            'order_count' => fake()->numberBetween(1, 20),
            'item_count' => fake()->numberBetween(1, 40),
        ];
    }
}
