<?php

namespace App\Services\Calculations;

/**
 * Platform hizmet bedeli hesaplama (Trendyol 8.49 TL + KDV / paket).
 */
class ServiceFeeCalculator
{
    public function __construct(
        private readonly VatCalculator $vat,
    ) {}

    /**
     * Platform hizmet bedelini hesaplar.
     *
     * @param  int  $count  Paket sayısı
     * @return array{amount_excl_vat: string, vat: string, total: string}
     */
    public function calculate(string $marketplace, string $orderType, int $count = 1): array
    {
        $config = config("marketplaces.{$marketplace}.platform_service_fee.{$orderType}");

        $amountExclVat = $config['amount_excl_vat'] ?? '8.49';
        $vatRate = $config['vat_rate'] ?? 20.0;

        $unitExclVat = bcround((string) $amountExclVat, 4);
        $totalExclVat = bcmul($unitExclVat, (string) $count, 4);
        $vatAmount = $this->vat->vatAmount(
            $this->vat->includeVat($totalExclVat, $vatRate),
            $vatRate
        );
        $total = $this->vat->includeVat($totalExclVat, $vatRate);

        return [
            'amount_excl_vat' => bcround($totalExclVat, 4),
            'vat' => bcround($vatAmount, 4),
            'total' => $total,
        ];
    }

    /**
     * Platform hizmet bedelinin yalnızca KDV hariç kısmı.
     */
    public function amountExcludingVat(string $marketplace, string $orderType): string
    {
        $result = $this->calculate($marketplace, $orderType, 1);

        return $result['amount_excl_vat'];
    }
}
