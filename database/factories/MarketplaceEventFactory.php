<?php

namespace Database\Factories;

use App\Models\MarketplaceEvent;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MarketplaceEvent>
 */
class MarketplaceEventFactory extends Factory
{
    protected $model = MarketplaceEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'marketplace_code' => 'trendyol',
            'event_type' => 'order_created',
            'source_reference' => 'ORDER-'.fake()->unique()->numberBetween(100000, 999999),
            'raw_payload' => [
                'orderNumber' => 'ORDER-'.fake()->numberBetween(100000, 999999),
                'status' => 'Created',
                'packageStatus' => 'Created',
            ],
            'status' => 'received',
            'processed_at' => null,
            'processing_error' => null,
        ];
    }
}
