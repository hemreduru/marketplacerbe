<?php

namespace App\Support;

/**
 * Bir pazaryerinin kâr hesabına giren tüm fee parametrelerinin çözülmüş hali.
 *
 * Değerler config/marketplaces/{code}.php'den gelir ve YALNIZCA fallback
 * tahminlerdir — settlement API'sinden gerçek değer geldiğinde onlar geçerlidir.
 * Tüm oranlar bcmath uyumlu string tutulur.
 */
final class MarketplaceFeeProfile
{
    /**
     * @param  array{base_desi: int|float, base_price: float|string, per_extra_desi_price: float|string}  $shippingTariff
     * @param  array<string, array{amount_excl_vat: string, vat_rate: string}>  $serviceFees
     */
    public function __construct(
        public readonly string $code,
        public readonly string $commissionBaseType,
        public readonly string $commissionDefaultRate,
        public readonly string $commissionVatRate,
        public readonly string $saleVatRate,
        public readonly string $shippingVatRate,
        public readonly string $stopajRate,
        public readonly array $shippingTariff,
        public readonly array $serviceFees,
    ) {}

    /**
     * Sipariş tipine göre platform hizmet bedeli; bilinmeyen tip standard'a düşer.
     *
     * @return array{amount_excl_vat: string, vat_rate: string}
     */
    public function serviceFee(string $orderType): array
    {
        return $this->serviceFees[$orderType]
            ?? $this->serviceFees['standard']
            ?? ['amount_excl_vat' => '0', 'vat_rate' => '20'];
    }
}
