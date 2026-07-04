<?php

namespace App\Services\Calculations;

use App\Services\Finance\AdSpendRepository;

/**
 * SKU bazlı reklam maliyeti dağıtımı.
 *
 * Gerçek harcama ad_metrics'ten okunur ve dönemdeki toplam satılan birime
 * bölünerek blended (harmanlanmış) birim maliyet çıkarılır. Kampanya→SKU
 * eşlemesi API'lerde olmadığı için v1 blended'dır; ileride ad_campaign_products
 * pivot'u ile SKU hassasiyetine geçilecek.
 */
class AdAllocator
{
    private const INTERNAL_SCALE = 6;

    private const RESULT_SCALE = 4;

    public function __construct(
        private readonly AdSpendRepository $spend = new AdSpendRepository,
    ) {}

    /**
     * Dönemdeki gerçek reklam harcamasını birim başına dağıtır.
     *
     * Formül: (toplam spend / dönemdeki toplam satılan birim) × bu kalemin birimi
     */
    public function blendedPerUnit(
        int $credentialId,
        string $marketplaceCode,
        string $periodStart,
        string $periodEnd,
        int $units,
        int $totalUnitsInPeriod,
    ): string {
        if ($units <= 0 || $totalUnitsInPeriod <= 0) {
            return '0.0000';
        }

        $totalSpend = $this->spend->totalSpend($credentialId, $marketplaceCode, $periodStart, $periodEnd);

        if (bccomp($totalSpend, '0', self::RESULT_SCALE) === 0) {
            return '0.0000';
        }

        $perUnit = bcdiv($totalSpend, (string) $totalUnitsInPeriod, self::INTERNAL_SCALE);

        return bcround(bcmul($perUnit, (string) $units, self::INTERNAL_SCALE), self::RESULT_SCALE);
    }

    /**
     * Config fallback ile SKU başına birim reklam maliyeti.
     *
     * @deprecated blendedPerUnit() kullanın — Stage 2'de ProfitCalculator geçince kaldırılacak.
     */
    public function perUnit(string $sku, string $marketplace, int $unitsSold, ?string $periodStart = null, ?string $periodEnd = null): string
    {
        if ($unitsSold <= 0) {
            return '0.0000';
        }

        $defaultAdCostPerUnit = (float) config("marketplaces.{$marketplace}.advertising.default_cost_per_unit", 0);

        if ($defaultAdCostPerUnit <= 0) {
            return '0.0000';
        }

        $total = bcdiv((string) $defaultAdCostPerUnit, '1', 4);

        return bcround(bcmul($total, (string) $unitsSold, 6), 4);
    }
}
