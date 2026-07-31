<?php

namespace App\Support;

/**
 * Bir kâr hesabının pazaryeri bağlamı.
 *
 * ProfitCalculator'daki hardcoded 'trendyol' değerlerinin yerini alır;
 * hesap hangi pazaryerinin siparişi içinse o pazaryerinin fee profili
 * ile yapılır. ProfitContextFactory üzerinden üretilir.
 */
final class ProfitContext
{
    public function __construct(
        public readonly MarketplaceFeeProfile $profile,
        public readonly ?int $credentialId = null,
        public readonly string $orderType = 'standard',
        public readonly float $returnRate = 0.0,
        public readonly string $adCostPerUnit = '0.0000',
    ) {}
}
