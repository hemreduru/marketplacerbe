<?php

namespace App\Services\Finance;

use App\Models\Order;
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
    ) {}

    public function forOrder(Order $order): ProfitContext
    {
        $code = $order->marketplace->slug ?? 'trendyol';

        return new ProfitContext(
            profile: $this->fees->profileFor($code),
            credentialId: $order->user_marketplace_credential_id,
        );
    }

    public function forMarketplace(string $marketplaceCode, ?int $credentialId = null): ProfitContext
    {
        return new ProfitContext(
            profile: $this->fees->profileFor($marketplaceCode),
            credentialId: $credentialId,
        );
    }
}
