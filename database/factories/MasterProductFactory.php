<?php

namespace Database\Factories;

use App\Models\MasterProduct;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MasterProduct>
 */
class MasterProductFactory extends Factory
{
    protected $model = MasterProduct::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->words(4, true),
            'brand' => fake()->company(),
            'sku' => 'SKU-'.fake()->unique()->bothify('###??##'),
            'barcode' => (string) fake()->unique()->ean13(),
            'cost_price' => fake()->randomFloat(4, 5, 500),
            'cost_price_vat_rate' => 20.00,
            'vat_rate' => 20.00,
            'weight_g' => fake()->numberBetween(50, 3000),
            'desi' => fake()->randomFloat(2, 0.5, 10),
            'packaging_cost' => fake()->randomFloat(4, 0, 5),
            'current_stock' => fake()->numberBetween(0, 200),
            'current_price' => fake()->randomFloat(4, 10, 1000),
            'pricing_strategy' => 'manual',
            'stock_buffer_strategy' => 'none',
            'stock_buffer_value' => 0,
            'version' => 0,
            'marketplace_specific_attributes' => null,
        ];
    }
}
