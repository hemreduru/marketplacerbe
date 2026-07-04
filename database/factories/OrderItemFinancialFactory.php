<?php

namespace Database\Factories;

use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItemFinancial>
 */
class OrderItemFinancialFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_item_id' => OrderItem::factory(),
            'order_id' => fn (array $attributes) => OrderItem::find($attributes['order_item_id'])->order_id,
            'marketplace_code' => 'trendyol',
            'order_date' => now()->toDateString(),
            'net_revenue' => '100.0000',
            'cogs' => '40.0000',
            'commission' => '15.0000',
            'service_fee' => '8.4900',
            'shipping' => '40.0000',
            'stopaj' => '1.0000',
            'ad_cost' => '0.0000',
            'return_cost' => '0.0000',
            'packaging' => '0.0000',
            'net_profit' => '-4.4900',
            'margin' => '-4.4900',
            'source' => ProfitSource::Estimate,
            'reconciliation_status' => ReconciliationStatus::Estimated,
            'estimated_net_profit' => '-4.4900',
            'estimated_at' => now(),
        ];
    }

    public function settled(): static
    {
        return $this->state(fn () => [
            'source' => ProfitSource::Settlement,
            'reconciliation_status' => ReconciliationStatus::Settled,
            'reconciled_at' => now(),
        ]);
    }
}
