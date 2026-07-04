<?php

namespace App\Models;

use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;
use Database\Factories\OrderItemFinancialFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Kalem bazlı kâr defteri satırı (hibrit tahmin/settlement).
 *
 * Etkin kolonlar güncel en iyi bilgiyi tutar; estimate_breakdown ilk tahmin
 * snapshot'ıdır ve mutabakat sapması ölçmek için değiştirilmez.
 *
 * @property int $id
 * @property int $order_item_id
 * @property int $order_id
 * @property int|null $user_marketplace_credential_id
 * @property int|null $master_product_id
 * @property string $marketplace_code
 * @property string $net_revenue
 * @property string $cogs
 * @property string $commission
 * @property string $service_fee
 * @property string $shipping
 * @property string $stopaj
 * @property string $ad_cost
 * @property string $return_cost
 * @property string $packaging
 * @property string $net_profit
 * @property string $margin
 * @property ProfitSource $source
 * @property ReconciliationStatus $reconciliation_status
 * @property string|null $estimated_net_profit
 * @property array<string, mixed>|null $estimate_breakdown
 * @property array<string, mixed>|null $actual_breakdown
 * @property array<string, string>|null $component_sources
 */
class OrderItemFinancial extends Model
{
    /** @use HasFactory<OrderItemFinancialFactory> */
    use HasFactory;

    protected $guarded = [];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'order_date' => 'date',
            'net_revenue' => 'decimal:4',
            'cogs' => 'decimal:4',
            'commission' => 'decimal:4',
            'service_fee' => 'decimal:4',
            'shipping' => 'decimal:4',
            'stopaj' => 'decimal:4',
            'ad_cost' => 'decimal:4',
            'return_cost' => 'decimal:4',
            'packaging' => 'decimal:4',
            'net_profit' => 'decimal:4',
            'margin' => 'decimal:4',
            'estimated_net_profit' => 'decimal:4',
            'source' => ProfitSource::class,
            'reconciliation_status' => ReconciliationStatus::class,
            'estimate_breakdown' => 'array',
            'actual_breakdown' => 'array',
            'component_sources' => 'array',
            'estimated_at' => 'datetime',
            'reconciled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OrderItem, $this>
     */
    public function item(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return BelongsTo<MasterProduct, $this>
     */
    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterProduct::class, 'master_product_id');
    }

    /**
     * @return BelongsTo<UserMarketplaceCredential, $this>
     */
    public function credential(): BelongsTo
    {
        return $this->belongsTo(UserMarketplaceCredential::class, 'user_marketplace_credential_id');
    }
}
