<?php

namespace App\Services\Calculations;

/**
 * Net KDV yükümlülüğü (KDV mahsuplaşması).
 *
 * Satış KDV'si (devlete borç) ile alış/iade/komisyon/kargo/platform bedeli
 * KDV iadelerini (devletten alacak) netleştirir.
 *
 * Negatif sonuç = KDV alacaklı pozisyon (normal, özellikle ihracatta).
 */
class NetVatLiability
{
    public function __construct(
        private readonly VatCalculator $vat,
        private readonly CommissionCalculator $commission,
    ) {}

    /**
     * @return array{sale_vat: string, purchase_vat_refund: string, commission_vat_refund: string, shipping_vat_refund: string, platform_fee_vat_refund: string, net_liability: string}
     */
    public function calculate(
        float|string|int $saleIncVat,
        float|string|int $saleVatRate,
        float|string|int $costIncVat,
        float|string|int $costVatRate,
        float|string|int $commissionAmount,
        float|string|int $commissionVatRate,
        float|string|int $shippingIncVat,
        float|string|int $shippingVatRate,
        float|string|int $platformFeeExclVat,
        float|string|int $platformFeeVatRate,
    ): array {
        $saleVat = $this->vat->vatAmount($saleIncVat, $saleVatRate);
        $purchaseVatRefund = $this->vat->vatAmount($costIncVat, $costVatRate);
        $commissionVatRefund = $this->commission->commissionVat($commissionAmount, $commissionVatRate);
        $shippingVatRefund = $this->vat->vatAmount($shippingIncVat, $shippingVatRate);
        $platformFeeIncVat = $this->vat->includeVat($platformFeeExclVat, $platformFeeVatRate);
        $platformFeeVatRefund = $this->vat->vatAmount($platformFeeIncVat, $platformFeeVatRate);

        $totalRefunds = '0.0000';
        $totalRefunds = bcadd($totalRefunds, $purchaseVatRefund, 6);
        $totalRefunds = bcadd($totalRefunds, $commissionVatRefund, 6);
        $totalRefunds = bcadd($totalRefunds, $shippingVatRefund, 6);
        $totalRefunds = bcadd($totalRefunds, $platformFeeVatRefund, 6);

        $netLiability = bcround(bcsub($saleVat, $totalRefunds, 6), 4);

        return [
            'sale_vat' => bcround($saleVat, 4),
            'purchase_vat_refund' => bcround($purchaseVatRefund, 4),
            'commission_vat_refund' => bcround($commissionVatRefund, 4),
            'shipping_vat_refund' => bcround($shippingVatRefund, 4),
            'platform_fee_vat_refund' => bcround($platformFeeVatRefund, 4),
            'net_liability' => $netLiability,
        ];
    }
}
