<?php

namespace Database\Factories;

use App\Models\MasterProduct;
use App\Models\PriceEvent;
use App\Support\Enums\PriceEventType;
use App\Support\Enums\StockEventSource;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PriceEvent>
 */
class PriceEventFactory extends Factory
{
    protected $model = PriceEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'master_product_id' => MasterProduct::factory(),
            'marketplace_listing_id' => null,
            'event_type' => PriceEventType::ManualChange->value,
            'source' => StockEventSource::User->value,
            'source_reference' => 'manual-'.fake()->unique()->numberBetween(1, 999999999),
            'new_price' => fake()->randomFloat(4, 10, 1000),
            'previous_price' => null,
            'occurred_at' => now(),
            'processed_at' => null,
        ];
    }
}
