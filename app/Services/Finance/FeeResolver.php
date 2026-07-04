<?php

namespace App\Services\Finance;

use App\Support\MarketplaceFeeProfile;

/**
 * Pazaryeri koduna göre fee profilini config'den çözer.
 *
 * Kâr motorundaki tüm 'trendyol' hardcode'larının yerini alır; her hesap
 * siparişin gerçek pazaryerinin parametreleriyle yapılır. Config değerleri
 * fallback tahmindir — settlement verisi geldiğinde SettlementReconciler
 * gerçek tutarları yazar.
 */
class FeeResolver
{
    /** @var array<string, MarketplaceFeeProfile> */
    private array $cache = [];

    public function profileFor(string $marketplaceCode): MarketplaceFeeProfile
    {
        return $this->cache[$marketplaceCode] ??= $this->build($marketplaceCode);
    }

    private function build(string $code): MarketplaceFeeProfile
    {
        /** @var array<string, mixed> $config */
        $config = config("marketplaces.{$code}", []);

        $serviceFees = [];
        /** @var array<string, array{amount_excl_vat?: float|string, vat_rate?: float|string}> $rawFees */
        $rawFees = $config['platform_service_fee'] ?? [];
        foreach ($rawFees as $orderType => $fee) {
            $serviceFees[$orderType] = [
                'amount_excl_vat' => (string) ($fee['amount_excl_vat'] ?? '0'),
                'vat_rate' => (string) ($fee['vat_rate'] ?? '20'),
            ];
        }

        return new MarketplaceFeeProfile(
            code: $code,
            commissionBaseType: (string) ($config['commission']['base_type'] ?? 'vat_excluded'),
            commissionDefaultRate: (string) ($config['commission']['default_rate'] ?? '15'),
            commissionVatRate: (string) ($config['vat_rates']['commission'] ?? '20'),
            saleVatRate: (string) ($config['vat_rates']['sale_default'] ?? '20'),
            shippingVatRate: (string) ($config['vat_rates']['shipping'] ?? '20'),
            stopajRate: (string) ($config['stopaj']['rate'] ?? '1'),
            shippingTariff: $config['shipping']['default_tariff'] ?? [
                'base_desi' => 5,
                'base_price' => 40.00,
                'per_extra_desi_price' => 5.00,
            ],
            serviceFees: $serviceFees !== [] ? $serviceFees : [
                'standard' => ['amount_excl_vat' => '0', 'vat_rate' => '20'],
            ],
        );
    }
}
