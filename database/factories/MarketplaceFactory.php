<?php

namespace Database\Factories;

use App\Models\Marketplace;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Marketplace>
 */
class MarketplaceFactory extends Factory
{
    protected $model = Marketplace::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'code' => strtoupper(Str::slug($name, '_')),
            'api_base_url' => 'https://apigw.example.com',
            'logo' => null,
            'is_active' => true,
            'config' => [],
        ];
    }

    public function trendyol(): static
    {
        return $this->state(fn () => [
            'name' => 'Trendyol',
            'slug' => 'trendyol',
            'code' => 'TRENDYOL',
            'api_base_url' => 'https://apigw.trendyol.com',
            'is_active' => true,
        ]);
    }
}
