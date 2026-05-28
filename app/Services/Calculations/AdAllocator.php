<?php

namespace App\Services\Calculations;

/**
 * SKU bazlı reklam maliyeti dağıtımı.
 *
 * Şu anda config'ten sabit yüzde veya 0 TL döner.
 * manual_ad_costs tablosu Faz 4'te reklam API entegrasyonuyla birlikte eklenecek.
 */
class AdAllocator
{
    /**
     * SKU başına birim reklam maliyetini hesaplar.
     *
     * Şimdilik config fallback; ileri PR'da manual_ad_costs tablosundan okur.
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
