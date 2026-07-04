<?php

namespace App\Jobs;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Services\Calculations\ProfitCalculator;
use App\Services\Calculations\ServiceFeeCalculator;
use App\Services\Finance\ProfitContextFactory;
use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;
use App\Support\MoneyAllocator;
use App\Support\ProfitBreakdown;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Sipariş ingest edildiğinde kalem bazlı tahmini net kârı yazar.
 *
 * Her order_item için order_item_financials'a source=estimate satırı upsert
 * eder. Platform hizmet bedeli sipariş başına 1 kez hesaplanıp kalemlere
 * net gelir payına göre (MoneyAllocator, kayıpsız) dağıtılır.
 *
 * Settlement kaynaklı satırlar (source != estimate) ASLA ezilmez — onların
 * sahibi SettlementReconciler'dır.
 */
class EstimateOrderProfitJob implements ShouldQueue
{
    use Queueable;

    /** @var int */
    public $tries = 3;

    /** @var array<int, int> */
    public $backoff = [30, 120, 600];

    public function __construct(
        public readonly int $orderId,
    ) {}

    public function handle(
        ProfitCalculator $calculator,
        ProfitContextFactory $contexts,
        ServiceFeeCalculator $serviceFee,
    ): void {
        $order = Order::with(['items.financial', 'items.master', 'marketplace'])->find($this->orderId);

        if (! $order || $order->items->isEmpty()) {
            return;
        }

        $ctx = $contexts->forOrder($order);
        $orderDate = ($order->order_date ?? $order->created_at)->toDateString();

        /** @var array<int, array{item: OrderItem, master: MasterProduct|null, breakdown: ProfitBreakdown}> $rows */
        $rows = [];
        foreach ($order->items as $item) {
            $master = $item->master ?? $this->resolveMaster($order, $item);
            $rows[] = [
                'item' => $item,
                'master' => $master,
                'breakdown' => $calculator->forOrderItem($item, $master, $ctx),
            ];
        }

        // Platform hizmet bedeli sipariş başına 1 kez → net gelir payına göre dağıt
        $feeTotal = $serviceFee->calculate($ctx->profile->code, $ctx->orderType, 1)['amount_excl_vat'];
        $feeShares = MoneyAllocator::distribute($feeTotal, array_map(
            static fn (array $row): string => $row['breakdown']->netRevenue,
            $rows
        ));

        foreach ($rows as $index => $row) {
            $item = $row['item'];
            $breakdown = $row['breakdown'];

            // Settlement ile mutabakatlanmış satırı tahminle ezme
            if ($item->financial && $item->financial->source !== ProfitSource::Estimate) {
                continue;
            }

            $feeShare = $feeShares[$index];
            $netProfit = bcround(bcsub($breakdown->netProfit, $feeShare, 6), 4);
            $margin = bccomp($breakdown->netRevenue, '0', 6) === 0
                ? '0.0000'
                : bcround(bcmul(bcdiv($netProfit, $breakdown->netRevenue, 6), '100', 6), 2);

            $estimateBreakdown = $breakdown->toArray();
            $estimateBreakdown['deductions']['service_fee'] = $feeShare;
            $estimateBreakdown['net_profit'] = $netProfit;

            OrderItemFinancial::updateOrCreate(
                ['order_item_id' => $item->id],
                [
                    'order_id' => $order->id,
                    'user_marketplace_credential_id' => $order->user_marketplace_credential_id,
                    'master_product_id' => $row['master']?->id,
                    'marketplace_code' => $ctx->profile->code,
                    'order_date' => $orderDate,
                    'net_revenue' => $breakdown->netRevenue,
                    'cogs' => $breakdown->deductions['cost_of_goods'],
                    'commission' => $breakdown->deductions['commission'],
                    'service_fee' => $feeShare,
                    'shipping' => $breakdown->deductions['shipping'],
                    'stopaj' => $breakdown->deductions['stopaj'],
                    'ad_cost' => $breakdown->deductions['ad_cost'],
                    'return_cost' => $breakdown->deductions['return_cost'],
                    'packaging' => $breakdown->deductions['packaging'],
                    'net_profit' => $netProfit,
                    'margin' => $margin,
                    'source' => ProfitSource::Estimate,
                    'reconciliation_status' => ReconciliationStatus::Estimated,
                    'estimated_net_profit' => $netProfit,
                    'estimate_breakdown' => $estimateBreakdown,
                    'component_sources' => null,
                    'estimated_at' => now(),
                ]
            );
        }
    }

    /**
     * Kalemde master bağlantısı yoksa barcode → sku sırasıyla eşleştirir
     * ve bulursa kaleme geri yazar (self-healing).
     */
    private function resolveMaster(Order $order, OrderItem $item): ?MasterProduct
    {
        $query = MasterProduct::where('user_id', $order->user_id);

        $master = null;
        if ($item->barcode) {
            $master = (clone $query)->where('barcode', $item->barcode)->first();
        }
        if (! $master && $item->merchant_sku) {
            $master = (clone $query)->where('sku', $item->merchant_sku)->first();
        }

        if ($master && ! $item->master_product_id) {
            $item->forceFill(['master_product_id' => $master->id])->save();
        }

        return $master;
    }
}
