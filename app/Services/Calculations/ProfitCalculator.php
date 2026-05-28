<?php

namespace App\Services\Calculations;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Support\ProfitBreakdown;

/**
 * Ana kârlılık hesaplama motoru.
 *
 * Tüm alt hesaplayıcıları (KDV, komisyon, hizmet bedeli, kargo, iade, paketleme, reklam)
 * birleştirerek net kâr hesaplar.
 *
 * Formül (tek kalem):
 *   net_profit = netRevenue - costOfGoods - commission - serviceFee - shipping
 *              - returnCost - adCost - packaging
 *
 * Formül (sipariş seviyesi — NOT: platform fee ve kargo sipariş başına 1 kez):
 *   net_profit = SUM(items_netRevenue) - SUM(items_costOfGoods, commission, return, ad, packaging)
 *              - shipping(order_level) - platform_fee(order_level)
 */
class ProfitCalculator
{
    public function __construct(
        private readonly VatCalculator $vat,
        private readonly CommissionCalculator $commission,
        private readonly ServiceFeeCalculator $serviceFee,
        private readonly ShippingCostCalculator $shipping,
        private readonly ReturnCostEstimator $returnCost,
        private readonly PackagingCostCalculator $packaging,
        private readonly AdAllocator $ads,
    ) {}

    /**
     * Tek sipariş kalemi için net kâr hesaplar.
     */
    public function forOrderItem(
        OrderItem $item,
        ?MasterProduct $master = null,
        string $orderType = 'standard',
        float $commissionRate = 15.0,
        string $commissionBaseType = 'vat_excluded',
        float $commissionVatRate = 20.0,
        array $shippingTariff = [],
        float $returnRate = 0.0,
    ): ProfitBreakdown {
        $vatRate = 20.0;
        $qty = $item->quantity;
        $unitPrice = (float) $item->price;
        $totalIncVat = $unitPrice * $qty;

        $netRevenue = $this->vat->excludeVat($totalIncVat, $vatRate);

        $deductions = [];
        $details = [];

        $costOfGoods = $master
            ? $this->vat->excludeVat((float) $master->cost_price * $qty, (float) $master->cost_price_vat_rate)
            : '0.0000';
        $deductions['cost_of_goods'] = bcround($costOfGoods, 4);

        $commissionAmt = $this->commission->amount($totalIncVat, $vatRate, $commissionRate, $commissionBaseType);
        $deductions['commission'] = bcround($commissionAmt, 4);

        $shippingTariff = $shippingTariff ?: config('marketplaces.trendyol.shipping.default_tariff');
        $shippingResult = $this->shipping->compute(
            $master ? (float) $master->desi : 1.0,
            $master ? $master->weight_g : 500,
            $shippingTariff
        );
        $deductions['shipping'] = $shippingResult['excl_vat'];

        $returnCost = $this->returnCost->expectedReturnCost($returnRate, (float) $shippingResult['total']);
        $deductions['return_cost'] = bcround($returnCost, 4);

        $adCost = $this->ads->perUnit($item->merchant_sku ?? '', 'trendyol', $qty);
        $deductions['ad_cost'] = bcround($adCost, 4);

        $packagingCost = $master
            ? $this->packaging->calculate($master)
            : ['excl_vat' => '0.0000'];
        $deductions['packaging'] = $packagingCost['excl_vat'];

        $totalDeductions = '0.0000';
        foreach ($deductions as $d) {
            $totalDeductions = bcadd($totalDeductions, $d, 6);
        }

        $netProfit = bcround(bcsub($netRevenue, $totalDeductions, 6), 4);

        $margin = bccomp($netRevenue, '0.0000', 6) === 0
            ? '0.0000'
            : bcround(bcmul(bcdiv($netProfit, $netRevenue, 6), '100', 6), 2);

        $totalCosts = bcsub($netRevenue, $netProfit, 6);
        $roi = bccomp($totalCosts, '0.0000', 6) === 0
            ? '0.0000'
            : bcround(bcmul(bcdiv($netProfit, $totalCosts, 6), '100', 6), 2);

        return new ProfitBreakdown(
            netRevenue: bcround($netRevenue, 4),
            netProfit: $netProfit,
            margin: $margin,
            roi: $roi,
            deductions: $deductions,
            details: [
                'item_id' => $item->id,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'vat_rate' => $vatRate,
                'sale_inc_vat' => $totalIncVat,
                'commission_rate' => $commissionRate,
                'shipping_result' => $shippingResult,
            ],
        );
    }

    /**
     * Sipariş seviyesinde net kâr hesaplar.
     * Platform service fee sipariş başına 1 kez düşülür.
     */
    public function forOrder(
        Order $order,
        string $orderType = 'standard',
        float $defaultCommissionRate = 15.0,
        string $commissionBaseType = 'vat_excluded',
        float $commissionVatRate = 20.0,
        array $shippingTariff = [],
        float $returnRate = 0.0,
    ): ProfitBreakdown {
        $items = $order->items;
        $itemsNetRevenue = '0.0000';
        $itemsCost = '0.0000';

        /** @var array<string, string> */
        $aggregatedDeductions = [];

        foreach ($items as $item) {
            $master = $item->master_product_id
                ? MasterProduct::find($item->master_product_id)
                : null;

            $breakdown = $this->forOrderItem($item, $master, $orderType, $defaultCommissionRate, $commissionBaseType, $commissionVatRate, $shippingTariff, $returnRate);

            $itemsNetRevenue = bcadd($itemsNetRevenue, $breakdown->netRevenue, 6);
            $itemsCost = bcadd($itemsCost, bcsub($breakdown->netRevenue, $breakdown->netProfit, 6), 6);

            foreach ($breakdown->deductions as $key => $val) {
                $aggregatedDeductions[$key] = bcadd($aggregatedDeductions[$key] ?? '0', $val, 6);
            }
        }

        $serviceFeeResult = $this->serviceFee->calculate('trendyol', $orderType, 1);
        $aggregatedDeductions['service_fee'] = $serviceFeeResult['amount_excl_vat'];

        $itemsCost = bcadd($itemsCost, $serviceFeeResult['amount_excl_vat'], 6);

        $netProfit = bcround(bcsub($itemsNetRevenue, $itemsCost, 6), 4);

        $margin = bccomp($itemsNetRevenue, '0.0000', 6) === 0
            ? '0.0000'
            : bcround(bcmul(bcdiv($netProfit, $itemsNetRevenue, 6), '100', 6), 2);

        $roi = bccomp($itemsCost, '0.0000', 6) === 0
            ? '0.0000'
            : bcround(bcmul(bcdiv($netProfit, $itemsCost, 6), '100', 6), 2);

        return new ProfitBreakdown(
            netRevenue: bcround($itemsNetRevenue, 4),
            netProfit: $netProfit,
            margin: $margin,
            roi: $roi,
            deductions: $aggregatedDeductions,
            details: [
                'order_id' => $order->id,
                'item_count' => $items->count(),
                'service_fee_once' => $serviceFeeResult['amount_excl_vat'],
            ],
        );
    }
}
