<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        return [
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'remote_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'barcode' => fake()->ean13(),
            'sku' => fake()->bothify('SKU-####'),
            'title' => fake()->words(3, true),
            'brand' => fake()->company(),
            'category_name' => fake()->word(),
            'category_id' => fake()->numberBetween(1, 5000),
            'price' => fake()->randomFloat(2, 10, 1000),
            'list_price' => fake()->randomFloat(2, 10, 1200),
            'stock' => fake()->numberBetween(0, 100),
            'currency' => 'TRY',
            'status' => 'active',
            'images' => [],
            'attributes' => [],
            'description' => fake()->sentence(),
            'product_url' => fake()->url(),
        ];
    }
}
