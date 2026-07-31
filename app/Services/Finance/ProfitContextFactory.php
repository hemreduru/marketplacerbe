<?php

namespace App\Services\Finance;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Calculations\AdAllocator;
use App\Support\ProfitContext;

/**
 * Sipariş veya pazaryeri kodundan ProfitContext üretir.
 *
 * Pazaryeri kodu siparişin marketplace ilişkisinden (slug) çözülür;
 * ilişki yoksa trendyol fallback kullanılır (legacy veriler için).
 */
class ProfitContextFactory
{
    public function __construct(
        private readonly FeeResolver $fees,
        private readonly AdAllocator $ads,
    ) {}

    public function forOrder(Order $order): ProfitContext
    {
        $code = $order->marketplace->slug ?? 'trendyol';
        $credentialId = $order->user_marketplace_credential_id;

        return new ProfitContext(
            profile: $this->fees->profileFor($code),
            credentialId: $credentialId,
            adCostPerUnit: $this->adCostPerUnit($order, $code, $credentialId),
        );
    }

    /**
     * Siparişin takvim ayının blended (harmanlanmış) birim reklam maliyeti:
     * dönemdeki gerçek reklam harcaması / dönemdeki toplam satılan birim.
     *
     * ponytail: context başına 1 aggregate sorgu; SKU-hassas dağıtım YAGNI (blended v1).
     */
    private function adCostPerUnit(Order $order, string $code, ?int $credentialId): string
    {
        if (! $credentialId) {
            return '0.0000';
        }

        $date = $order->order_date ?? $order->created_at;
        if (! $date) {
            return '0.0000';
        }

        $start = $date->copy()->startOfMonth();
        $end = $date->copy()->endOfMonth();

        $totalUnits = (int) OrderItem::whereHas('order', function ($query) use ($credentialId, $start, $end) {
            $query->where('user_marketplace_credential_id', $credentialId)
                ->whereBetween('order_date', [$start, $end]);
        })->sum('quantity');

        if ($totalUnits <= 0) {
            return '0.0000';
        }

        return $this->ads->blendedPerUnit($credentialId, $code, $start->toDateString(), $end->toDateString(), 1, $totalUnits);
    }

    public function forMarketplace(string $marketplaceCode, ?int $credentialId = null): ProfitContext
    {
        return new ProfitContext(
            profile: $this->fees->profileFor($marketplaceCode),
            credentialId: $credentialId,
        );
    }
}
