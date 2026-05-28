<?php

namespace App\Services\Calculations;

/**
 * İade maliyeti tahmini.
 *
 * Formül: returnRate * shippingCost * 2 (gidiş-dönüş ortalaması)
 */
class ReturnCostEstimator
{
    /**
     * Beklenen iade maliyetini hesaplar.
     *
     * @param  float|string  $returnRate  İade oranı (0.10 = %10)
     * @param  float|string|int  $shippingCost  KDV dahil kargo ücreti
     */
    public function expectedReturnCost(float|string $returnRate, float|string|int $shippingCost): string
    {
        $rate = (string) $returnRate;

        if (bccomp($rate, '0', 6) === 0) {
            return '0.0000';
        }

        $roundTrip = bcmul((string) $shippingCost, '2', 6);

        return bcround(bcmul($rate, $roundTrip, 6), 4);
    }
}
