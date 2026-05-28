<?php

namespace App\Services\Calculations;

/**
 * KDV (VAT) ayrıştırma ve birleştirme hesaplamaları.
 * Tüm işlemler bcmath ile yapılır — float kullanılmaz.
 */
class VatCalculator
{
    /**
     * İç işlem hassasiyeti.
     */
    private const INTERNAL_SCALE = 6;

    /**
     * Dönüş hassasiyeti.
     */
    private const RESULT_SCALE = 4;

    /**
     * KDV dahil fiyattan KDV hariç fiyat hesaplar.
     *
     * Formül: priceIncVat / (1 + vatRate / 100)
     */
    public function excludeVat(float|string|int $incVat, float|string|int $rate): string
    {
        $rate = (string) $rate;

        if (bccomp($rate, '0', self::INTERNAL_SCALE) === 0) {
            return bcround((string) $incVat, self::RESULT_SCALE);
        }

        $divisor = bcadd('1', bcdiv($rate, '100', self::INTERNAL_SCALE), self::INTERNAL_SCALE);

        return bcround(bcdiv((string) $incVat, $divisor, self::INTERNAL_SCALE), self::RESULT_SCALE);
    }

    /**
     * KDV tutarını hesaplar.
     *
     * Formül: priceIncVat - priceExVat
     */
    public function vatAmount(float|string|int $incVat, float|string|int $rate): string
    {
        $exVat = $this->excludeVat($incVat, $rate);

        return bcround(bcsub((string) $incVat, $exVat, self::INTERNAL_SCALE), self::RESULT_SCALE);
    }

    /**
     * KDV hariç fiyata KDV ekler.
     *
     * Formül: priceExVat * (1 + vatRate / 100)
     */
    public function includeVat(float|string|int $exVat, float|string|int $rate): string
    {
        $rate = (string) $rate;

        $multiplier = bcadd('1', bcdiv($rate, '100', self::INTERNAL_SCALE), self::INTERNAL_SCALE);

        return bcround(bcmul((string) $exVat, $multiplier, self::INTERNAL_SCALE), self::RESULT_SCALE);
    }
}
