<?php

namespace Database\Factories;

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\SyncDispatchEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SyncDispatchEntry>
 */
class SyncDispatchEntryFactory extends Factory
{
    protected $model = SyncDispatchEntry::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'master_product_id' => MasterProduct::factory(),
            'marketplace_listing_id' => MarketplaceListing::factory(),
            'mutation_type' => 'stock',
            'payload_json' => ['stock' => 10],
            'status' => 'pending',
            'attempt_count' => 0,
            'last_attempt_at' => null,
            'last_error' => null,
            'next_attempt_at' => null,
        ];
    }
}
