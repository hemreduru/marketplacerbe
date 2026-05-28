<?php

namespace App\Services\Calculations;

/**
 * Pazaryeri komisyon hesaplamaları.
 * Komisyon matrahı (vat_excluded|vat_included) marketplace config'ten okunur.
 */
class CommissionCalculator
{
    public function __construct(
        private readonly VatCalculator $vat,
    ) {}

    /**
     * Komisyon matrahını hesaplar.
     */
    public function base(float|string|int $saleIncVat, float|string|int $vatRate, string $baseType): string
    {
        if ($baseType === 'vat_excluded') {
            return $this->vat->excludeVat($saleIncVat, $vatRate);
        }

        // vat_included: satış fiyatının tamamı matrahtır
        return bcround((string) $saleIncVat, 4);
    }

    /**
     * Komisyon tutarını hesaplar.
     *
     * Formül: base * (commissionRate / 100)
     */
    public function amount(float|string|int $saleIncVat, float|string|int $vatRate, float|string|int $commissionRate, string $baseType): string
    {
        $base = $this->base($saleIncVat, $vatRate, $baseType);
        $rate = (string) $commissionRate;

        return bcround(
            bcmul($base, bcdiv($rate, '100', 6), 6),
            4
        );
    }

    /**
     * Devlete ödenen komisyon KDV'si (alacak kalem).
     */
    public function commissionVat(float|string|int $commissionAmount, float|string|int $vatRate): string
    {
        return bcround(
            bcmul((string) $commissionAmount, bcdiv((string) $vatRate, '100', 6), 6),
            4
        );
    }

    /**
     * Toplam komisyon kesintisi (komisyon + KDV).
     */
    public function totalDeduction(float|string|int $saleIncVat, float|string|int $vatRate, float|string|int $commissionRate, string $baseType, float|string|int $commissionVatRate): string
    {
        $amt = $this->amount($saleIncVat, $vatRate, $commissionRate, $baseType);
        $vatCost = $this->commissionVat($amt, $commissionVatRate);

        return bcround(bcadd($amt, $vatCost, 6), 4);
    }
}
