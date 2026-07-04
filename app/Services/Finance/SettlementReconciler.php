<?php

namespace App\Services\Finance;

use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\OrderItemFinancial;
use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;
use App\Support\MoneyAllocator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Settlement gerçeklerini kalem bazlı kâr defterine (order_item_financials) işler.
 *
 * Tahmin (source=estimate) satırlarındaki komisyon/kargo/iade bileşenlerini
 * pazaryerinden gelen GERÇEK tutarlarla değiştirir. Sipariş seviyesindeki
 * tutarlar MoneyAllocator ile kayıpsız dağıtılır (artık en büyük kaleme).
 *
 * State machine: estimated → partially_settled → settled (+ reopened_return).
 * `settled` kuralı: komisyon VE kargo settlement kaynaklı VE iade penceresi
 * (config finance.return_window_days) kapalı. Platform hizmet bedeli sabit
 * tarife olduğundan (deterministik) settled şartına dahil edilmez.
 *
 * İdempotent: bileşenler her koşuda kaynaktan yeniden hesaplanır (increment
 * yok) — aynı dönem tekrar sync edilirse sonuç aynı kalır.
 */
class SettlementReconciler
{
    private const SCALE = 4;

    public function __construct(
        private readonly ReturnCostResolver $returns,
    ) {}

    /**
     * Credential'ın verilen penceredeki Sale settlement satırlarını kalemlere işler.
     *
     * @param  array<string, float|string>  $cargoMap  order_number => gerçek kargo tutarı (opsiyonel)
     * @return array{reconciled: int, skipped: int}
     */
    public function reconcileCredential(int $credentialId, string $fromYmd, string $toYmd, array $cargoMap = []): array
    {
        $stats = ['reconciled' => 0, 'skipped' => 0];

        // Sale satırlarını sipariş numarasına göre grupla (kalem satırları olabilir)
        $saleRows = FinancialTransaction::query()
            ->where('user_marketplace_credential_id', $credentialId)
            ->where('transaction_type', 'Sale')
            ->whereBetween('transaction_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->whereNotNull('order_number')
            ->get()
            ->groupBy('order_number');

        $returnCostMap = $this->returns->approvedReturnCostMap($credentialId, $fromYmd, $toYmd);

        foreach ($saleRows as $orderNumber => $rows) {
            $order = Order::query()
                ->where('user_marketplace_credential_id', $credentialId)
                ->where('order_number', (string) $orderNumber)
                ->with('items.financial')
                ->first();

            if (! $order || $order->items->isEmpty()) {
                $stats['skipped']++;

                continue;
            }

            $this->reconcileOrder(
                $order,
                $rows,
                isset($cargoMap[$orderNumber]) ? (string) $cargoMap[$orderNumber] : null,
                $returnCostMap->get((string) $orderNumber),
            );

            $stats['reconciled']++;
        }

        // Settlement satırı gelmemiş ama iade penceresi/iadesi olan siparişleri de işle
        foreach ($returnCostMap as $orderNumber => $returnCost) {
            if (isset($saleRows[$orderNumber])) {
                continue; // yukarıda işlendi
            }

            $order = Order::query()
                ->where('user_marketplace_credential_id', $credentialId)
                ->where('order_number', (string) $orderNumber)
                ->with('items.financial')
                ->first();

            if ($order && $order->items->isNotEmpty()) {
                $this->reconcileOrder($order, collect(), null, $returnCost);
                $stats['reconciled']++;
            }
        }

        return $stats;
    }

    /**
     * Tek siparişin kalemlerine settlement gerçeklerini uygular.
     *
     * @param  Collection<int, FinancialTransaction>  $saleRows
     */
    public function reconcileOrder(Order $order, Collection $saleRows, ?string $actualCargo, ?string $returnCost): void
    {
        $financials = $order->items
            ->map(fn ($item) => $item->financial)
            ->filter()
            ->values();

        if ($financials->isEmpty()) {
            return;
        }

        // Sipariş seviyesi gerçek komisyon (satır bazlı varsa toplanır)
        $actualCommission = null;
        if ($saleRows->isNotEmpty()) {
            $actualCommission = '0.0000';
            foreach ($saleRows as $row) {
                $actualCommission = bcadd($actualCommission, (string) $row->commission, self::SCALE);
            }
        }

        // Dağıtım ağırlığı: kalemin net geliri (yoksa eşit)
        $weights = $financials
            ->map(fn (OrderItemFinancial $f): string => (string) $f->net_revenue)
            ->all();

        $commissionShares = $actualCommission !== null
            ? MoneyAllocator::distribute($actualCommission, $weights)
            : null;

        $cargoShares = $actualCargo !== null
            ? MoneyAllocator::distribute(bcround($actualCargo, self::SCALE), $weights)
            : null;

        $returnShares = $returnCost !== null && bccomp($returnCost, '0', self::SCALE) === 1
            ? MoneyAllocator::distribute($returnCost, $weights)
            : null;

        $windowClosed = $this->returnWindowClosed($order);

        foreach ($financials as $index => $financial) {
            $sources = $financial->component_sources ?? [];

            if ($commissionShares !== null) {
                $financial->commission = $commissionShares[$index];
                $sources['commission'] = ProfitSource::Settlement->value;
            }

            if ($cargoShares !== null) {
                $financial->shipping = $cargoShares[$index];
                $sources['shipping'] = ProfitSource::Settlement->value;
            }

            if ($returnShares !== null) {
                $financial->return_cost = $returnShares[$index];
                $sources['return_cost'] = ProfitSource::Settlement->value;
            }

            $financial->component_sources = $sources;
            $this->recompute($financial);

            $financial->source = $this->resolveSource($sources);
            $financial->reconciliation_status = $this->resolveStatus($sources, $windowClosed, $returnShares !== null);
            $financial->actual_breakdown = [
                'commission' => $financial->commission,
                'shipping' => $financial->shipping,
                'return_cost' => $financial->return_cost,
            ];
            $financial->reconciled_at = now();
            $financial->save();
        }
    }

    /**
     * Etkin kolonlardan net kâr + marjı yeniden hesaplar (kaynaktan, increment yok).
     */
    protected function recompute(OrderItemFinancial $financial): void
    {
        $deductions = ['cogs', 'commission', 'service_fee', 'shipping', 'stopaj', 'ad_cost', 'return_cost', 'packaging'];

        $total = '0.0000';
        foreach ($deductions as $column) {
            $total = bcadd($total, (string) $financial->{$column}, 6);
        }

        $netRevenue = (string) $financial->net_revenue;
        $netProfit = bcround(bcsub($netRevenue, $total, 6), self::SCALE);

        $financial->net_profit = $netProfit;
        $financial->margin = bccomp($netRevenue, '0', 6) === 0
            ? '0.0000'
            : bcround(bcmul(bcdiv($netProfit, $netRevenue, 6), '100', 6), 2);
    }

    /**
     * @param  array<string, string>  $sources
     */
    protected function resolveSource(array $sources): ProfitSource
    {
        $settled = array_filter($sources, fn (string $s) => $s === ProfitSource::Settlement->value);

        if ($settled === []) {
            return ProfitSource::Estimate;
        }

        // Komisyon + kargo settlement ise ana fee'ler gerçek demektir
        if (isset($settled['commission']) && isset($settled['shipping'])) {
            return ProfitSource::Settlement;
        }

        return ProfitSource::Mixed;
    }

    /**
     * @param  array<string, string>  $sources
     */
    protected function resolveStatus(array $sources, bool $windowClosed, bool $hasReturn): ReconciliationStatus
    {
        if ($hasReturn) {
            return $windowClosed ? ReconciliationStatus::Settled : ReconciliationStatus::ReopenedReturn;
        }

        $commissionSettled = ($sources['commission'] ?? null) === ProfitSource::Settlement->value;
        $shippingSettled = ($sources['shipping'] ?? null) === ProfitSource::Settlement->value;

        if ($commissionSettled && $shippingSettled && $windowClosed) {
            return ReconciliationStatus::Settled;
        }

        if ($commissionSettled || $shippingSettled) {
            return ReconciliationStatus::PartiallySettled;
        }

        return ReconciliationStatus::Estimated;
    }

    protected function returnWindowClosed(Order $order): bool
    {
        $windowDays = (int) config('finance.return_window_days', 15);
        $orderDate = $order->order_date ?? $order->created_at;

        return Carbon::parse($orderDate)->addDays($windowDays)->isPast();
    }
}
