<?php

namespace App\Services\Calculations;

/**
 * E-ticaret aracı hizmet sağlayıcı stopajı hesaplama.
 *
 * 7524 sayılı Kanun + 9284 sayılı Cumhurbaşkanı Kararı (RG 32760) gereği
 * 1 Ocak 2025'ten itibaren tüm pazaryerleri, satıcı adına KDV hariç satış
 * bedeli (matrah) üzerinden %1 gelir/kurumlar vergisi stopajı keser.
 *
 * Not: Stopaj yıl sonunda mahsup edilebilir; burada P&L maliyet kalemi
 * olarak modellenir (nakit akışı etkisi kesindir).
 */
class StopajCalculator
{
    private const INTERNAL_SCALE = 6;

    private const RESULT_SCALE = 4;

    public function __construct(
        private readonly VatCalculator $vat,
    ) {}

    /**
     * KDV dahil satış tutarından stopaj tutarını hesaplar.
     *
     * Formül: excludeVat(saleIncVat, saleVatRate) × rate / 100
     */
    public function amount(float|string|int $saleIncVat, float|string|int $saleVatRate, string $rate = '1.0'): string
    {
        if (bccomp($rate, '0', self::INTERNAL_SCALE) === 0) {
            return '0.0000';
        }

        $base = $this->vat->excludeVat($saleIncVat, $saleVatRate);

        return bcround(
            bcmul($base, bcdiv($rate, '100', self::INTERNAL_SCALE), self::INTERNAL_SCALE),
            self::RESULT_SCALE
        );
    }
}
