<?php

namespace App\Services\Calculations;

/**
 * Kargo maliyeti hesaplama (desi/ağırlık tabanlı).
 *
 * Formül: effective_desi = max(desi, weight_g / 1000 / 5)
 * Ücretlendirme: base_desi aşımında per_extra_desi_price eklenir.
 */
class ShippingCostCalculator
{
    /**
     * 1 desi = 5 kg (varyasyon: 1000g başına 0.2 desi)
     */
    private const GRAMS_PER_DESI = 5000;

    public function __construct(
        private readonly VatCalculator $vat,
    ) {}

    /**
     * Kargo ücretini hesaplar.
     *
     * @param  array<string, string|int>  $tariff  ['base_desi' => 5, 'base_price' => '40.00', 'per_extra_desi_price' => '5.00']
     * @return array{excl_vat: string, vat: string, total: string}
     */
    public function compute(float|string|int $desi, int $weightG, array $tariff): array
    {
        $weightDesi = bcdiv((string) $weightG, (string) self::GRAMS_PER_DESI, 4);
        $effectiveDesi = bcround(
            bccomp((string) $desi, $weightDesi, 4) >= 0 ? (string) $desi : $weightDesi,
            4
        );

        $baseDesi = (string) $tariff['base_desi'];
        $basePrice = (string) $tariff['base_price'];
        $perExtra = (string) $tariff['per_extra_desi_price'];

        if (bccomp($effectiveDesi, $baseDesi, 4) <= 0) {
            $exclVat = bcround($basePrice, 4);
        } else {
            $extraDesi = bcsub($effectiveDesi, $baseDesi, 4);
            $extraCost = bcmul($extraDesi, $perExtra, 4);
            $exclVat = bcround(bcadd($basePrice, $extraCost, 4), 4);
        }

        $vatRate = 20.0;
        $incVat = $this->vat->includeVat($exclVat, $vatRate);
        $vatAmount = $this->vat->vatAmount($incVat, $vatRate);

        return [
            'excl_vat' => bcround($exclVat, 4),
            'vat' => bcround($vatAmount, 4),
            'total' => $incVat,
        ];
    }
}
