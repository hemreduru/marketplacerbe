<?php

namespace Database\Factories;

use App\Models\MarketplaceListing;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MarketplaceListing>
 */
class MarketplaceListingFactory extends Factory
{
    protected $model = MarketplaceListing::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'master_product_id' => null,
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'remote_product_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'remote_sku' => 'RSKU-'.fake()->bothify('###??##'),
            'remote_barcode' => (string) fake()->ean13(),
            'listing_status' => 'active',
            'listed_price' => fake()->randomFloat(4, 10, 1000),
            'listed_stock' => fake()->numberBetween(0, 100),
            'listing_url' => fake()->url(),
            'category_path' => 'Elektronik > Telefon > Aksesuar',
            'attributes_json' => null,
            'last_synced_at' => null,
            'sync_status' => 'pending',
            'last_sync_error' => null,
        ];
    }
}
