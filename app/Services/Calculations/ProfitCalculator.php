<?php

namespace App\Services\Calculations;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Finance\ProfitContextFactory;
use App\Support\ProfitBreakdown;
use App\Support\ProfitContext;

/**
 * Ana kârlılık hesaplama motoru.
 *
 * Tüm alt hesaplayıcıları (KDV, komisyon, hizmet bedeli, kargo, stopaj,
 * iade, paketleme, reklam) birleştirerek net kâr hesaplar. Pazaryeri
 * parametreleri ProfitContext üzerinden gelir — hardcoded marketplace yok.
 *
 * Formül (tek kalem):
 *   net_profit = netRevenue - costOfGoods - commission - shipping - stopaj
 *              - returnCost - adCost - packaging
 *
 * Formül (sipariş seviyesi — NOT: platform fee ve kargo sipariş başına 1 kez):
 *   net_profit = SUM(items_netRevenue) - SUM(items deductions)
 *              - platform_fee(order_level)
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
        private readonly StopajCalculator $stopaj,
        private readonly ProfitContextFactory $contexts,
    ) {}

    /**
     * Tek sipariş kalemi için net kâr hesaplar.
     *
     * Context verilmezse kalemin siparişinin pazaryerinden çözülür.
     */
    public function forOrderItem(
        OrderItem $item,
        ?MasterProduct $master = null,
        ?ProfitContext $ctx = null,
    ): ProfitBreakdown {
        $ctx ??= $this->contexts->forOrder($item->order);
        $profile = $ctx->profile;

        // Satış KDV oranı: ürünün gerçek oranı > pazaryeri varsayılanı
        $vatRate = (string) ($master->vat_rate ?? $profile->saleVatRate);
        $qty = $item->quantity;
        $unitPrice = (string) $item->price;
        $totalIncVat = bcmul($unitPrice, (string) $qty, 4);

        $netRevenue = $this->vat->excludeVat($totalIncVat, $vatRate);

        $deductions = [];

        $costOfGoods = $master
            ? $this->vat->excludeVat(bcmul((string) $master->cost_price, (string) $qty, 4), (string) $master->cost_price_vat_rate)
            : '0.0000';
        $deductions['cost_of_goods'] = bcround($costOfGoods, 4);

        // Komisyon: kalemde gerçek (settlement) oran varsa o, yoksa config fallback
        $commissionRate = $item->commission_rate > 0
            ? (string) $item->commission_rate
            : $profile->commissionDefaultRate;
        $commissionAmt = $this->commission->amount($totalIncVat, $vatRate, $commissionRate, $profile->commissionBaseType);
        $deductions['commission'] = bcround($commissionAmt, 4);

        $shippingResult = $this->shipping->compute(
            $master ? (string) $master->desi : '1',
            $master ? $master->weight_g : 500,
            $profile->shippingTariff,
            $profile->shippingVatRate,
        );
        $deductions['shipping'] = $shippingResult['excl_vat'];

        // 7524 sayılı Kanun: KDV hariç matrah üzerinden %1 stopaj
        $deductions['stopaj'] = $this->stopaj->amount($totalIncVat, $vatRate, $profile->stopajRate);

        $returnCost = $this->returnCost->expectedReturnCost($ctx->returnRate, (float) $shippingResult['total']);
        $deductions['return_cost'] = bcround($returnCost, 4);

        // Reklam: dönem bazlı blended birim maliyet context'ten gelir (ProfitContextFactory)
        $adCost = bcmul($ctx->adCostPerUnit, (string) $qty, 6);
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
                'marketplace' => $profile->code,
                'unit_price' => $unitPrice,
                'quantity' => $qty,
                'vat_rate' => $vatRate,
                'sale_inc_vat' => $totalIncVat,
                'commission_rate' => $commissionRate,
                'commission_base_type' => $profile->commissionBaseType,
                'stopaj_rate' => $profile->stopajRate,
                'shipping_result' => $shippingResult,
            ],
        );
    }

    /**
     * Sipariş seviyesinde net kâr hesaplar.
     * Platform service fee sipariş başına 1 kez düşülür.
     */
    public function forOrder(Order $order, ?ProfitContext $ctx = null): ProfitBreakdown
    {
        $ctx ??= $this->contexts->forOrder($order);

        $items = $order->items;
        $itemsNetRevenue = '0.0000';
        $itemsCost = '0.0000';

        /** @var array<string, string> */
        $aggregatedDeductions = [];

        foreach ($items as $item) {
            $master = $item->master_product_id
                ? MasterProduct::find($item->master_product_id)
                : null;

            $breakdown = $this->forOrderItem($item, $master, $ctx);

            $itemsNetRevenue = bcadd($itemsNetRevenue, $breakdown->netRevenue, 6);
            $itemsCost = bcadd($itemsCost, bcsub($breakdown->netRevenue, $breakdown->netProfit, 6), 6);

            foreach ($breakdown->deductions as $key => $val) {
                $aggregatedDeductions[$key] = bcadd($aggregatedDeductions[$key] ?? '0', $val, 6);
            }
        }

        $serviceFeeResult = $this->serviceFee->calculate($ctx->profile->code, $ctx->orderType, 1);
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
                'marketplace' => $ctx->profile->code,
                'item_count' => $items->count(),
                'service_fee_once' => $serviceFeeResult['amount_excl_vat'],
            ],
        );
    }
}
