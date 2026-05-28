<?php

namespace Database\Factories;

use App\Models\MasterProduct;
use App\Models\StockEvent;
use App\Support\Enums\StockEventSource;
use App\Support\Enums\StockEventType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<StockEvent>
 */
class StockEventFactory extends Factory
{
    protected $model = StockEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'event_uuid' => (string) Str::uuid(),
            'master_product_id' => MasterProduct::factory(),
            'marketplace_listing_id' => null,
            'event_type' => StockEventType::Sale->value,
            'source' => StockEventSource::Trendyol->value,
            'source_reference' => 'order-'.fake()->unique()->numberBetween(1, 999999999),
            'quantity_delta' => -1,
            'occurred_at' => now(),
            'processed_at' => null,
        ];
    }
}
