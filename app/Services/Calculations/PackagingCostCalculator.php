<?php

namespace App\Services\Calculations;

use App\Models\MasterProduct;

/**
 * Paketleme maliyeti hesaplama.
 * MasterProduct.packaging_cost KDV dahil tutardır.
 */
class PackagingCostCalculator
{
    public function __construct(
        private readonly VatCalculator $vat,
    ) {}

    /**
     * @return array{excl_vat: string, vat: string, total: string}
     */
    public function calculate(MasterProduct $master): array
    {
        return $this->calculateFromCost(
            (string) $master->packaging_cost,
            (float) $master->vat_rate
        );
    }

    /**
     * Raw değerlerle hesaplama (Eloquent model bağımlılığı olmadan).
     *
     * @return array{excl_vat: string, vat: string, total: string}
     */
    public function calculateFromCost(string $totalIncVat, float $vatRate): array
    {
        if (bccomp($totalIncVat, '0', 6) === 0) {
            return ['excl_vat' => '0.0000', 'vat' => '0.0000', 'total' => '0.0000'];
        }

        $exclVat = $this->vat->excludeVat($totalIncVat, $vatRate);
        $vatAmount = $this->vat->vatAmount($totalIncVat, $vatRate);

        return [
            'excl_vat' => bcround($exclVat, 4),
            'vat' => bcround($vatAmount, 4),
            'total' => bcround($totalIncVat, 4),
        ];
    }
}
