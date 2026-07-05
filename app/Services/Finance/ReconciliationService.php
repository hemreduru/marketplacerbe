<?php

namespace App\Services\Finance;

use App\Models\FinancialTransaction;
use App\Models\OrderItemFinancial;
use App\Models\User;
use App\Support\Enums\ProfitSource;
use Illuminate\Support\Collection;

/**
 * Tahmin ↔ gerçek mutabakat raporu ve "para iade" anomali tespiti.
 *
 * deviation_pct = (gerçek − tahmin) / |tahmin| × 100. Portföy hedefi <%2.
 * Anomaliler GERİ ALINABİLİR fee hatalarını işaret eder: fazla kesilen
 * komisyon, tahminden aşırı sapan kargo (yanlış desi faturalama) ve ceza
 * satırları. Her anomali kanıt olarak kaynak kayda işaret eder.
 * Reklam sapması anomali DEĞİLDİR (blended dağıtım tahmini).
 */
class ReconciliationService
{
    private const SCALE = 4;

    /**
     * SKU bazında tahmin/gerçek karşılaştırması (settlement görmüş kalemler).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function bySku(User $user, string $fromYmd, string $toYmd): Collection
    {
        return $this->settledItems($user, $fromYmd, $toYmd)
            ->groupBy('master_product_id')
            ->map(function (Collection $group) {
                $first = $group->first();
                $estimated = $this->sumColumn($group, 'estimated_net_profit');
                $actual = $this->sumColumn($group, 'net_profit');

                /** @var array<string, mixed> $row */
                $row = [
                    'master_product_id' => $first->master_product_id,
                    'sku' => $first->master->sku ?? null,
                    'title' => $first->master->title ?? null,
                    'items' => $group->count(),
                    'estimated_net_profit' => $estimated,
                    'actual_net_profit' => $actual,
                    'deviation_pct' => $this->deviationPct($estimated, $actual),
                ];

                return $row;
            })
            ->sortByDesc(fn (array $row) => abs((float) $row['deviation_pct']))
            ->values();
    }

    /**
     * Sipariş bazında tahmin/gerçek karşılaştırması.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function byOrder(User $user, string $fromYmd, string $toYmd): Collection
    {
        return $this->settledItems($user, $fromYmd, $toYmd)
            ->groupBy('order_id')
            ->map(function (Collection $group) {
                $first = $group->first();
                $estimated = $this->sumColumn($group, 'estimated_net_profit');
                $actual = $this->sumColumn($group, 'net_profit');

                /** @var array<string, mixed> $row */
                $row = [
                    'order_id' => $first->order_id,
                    'order_number' => $first->order->order_number ?? null,
                    'estimated_net_profit' => $estimated,
                    'actual_net_profit' => $actual,
                    'deviation_pct' => $this->deviationPct($estimated, $actual),
                ];

                return $row;
            })
            ->sortByDesc(fn (array $row) => abs((float) $row['deviation_pct']))
            ->values();
    }

    /**
     * Portföy geneli sapma özeti — launch gate metriği (<%2 hedef).
     *
     * @return array{estimated: string, actual: string, deviation_pct: string, settled_items: int}
     */
    public function portfolioDeviation(User $user, string $fromYmd, string $toYmd): array
    {
        $items = $this->settledItems($user, $fromYmd, $toYmd);
        $estimated = $this->sumColumn($items, 'estimated_net_profit');
        $actual = $this->sumColumn($items, 'net_profit');

        return [
            'estimated' => $estimated,
            'actual' => $actual,
            'deviation_pct' => $this->deviationPct($estimated, $actual),
            'settled_items' => $items->count(),
        ];
    }

    /**
     * Geri alınabilir fee anomalileri ("para iade" ekranının veri kaynağı).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function anomalies(User $user, string $fromYmd, string $toYmd): Collection
    {
        $threshold = (string) config('finance.deviation_alert_threshold', 2.0);
        $anomalies = collect();

        foreach ($this->settledItems($user, $fromYmd, $toYmd) as $item) {
            $estimate = $item->estimate_breakdown['deductions'] ?? [];

            // 1) Komisyon fazla kesimi: gerçek > tahmin × (1 + eşik)
            $estCommission = (string) ($estimate['commission'] ?? '0');
            if ($this->exceedsThreshold($estCommission, (string) $item->commission, $threshold)) {
                $anomalies->push($this->anomalyRow($item, 'commission_overcharge', $estCommission, (string) $item->commission));
            }

            // 2) Kargo sapması: yanlış desi faturalama şüphesi
            $estShipping = (string) ($estimate['shipping'] ?? '0');
            if ($this->exceedsThreshold($estShipping, (string) $item->shipping, $threshold)) {
                $anomalies->push($this->anomalyRow($item, 'shipping_overcharge', $estShipping, (string) $item->shipping));
            }
        }

        // 3) Ceza satırları: doğrudan itiraz edilebilir kesintiler
        $penalties = FinancialTransaction::query()
            ->whereHas('credential', fn ($q) => $q->where('user_id', $user->id))
            ->where('transaction_type', 'Deduction')
            ->whereBetween('transaction_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->where(function ($q) {
                $q->where('description', 'like', '%ceza%')
                    ->orWhere('description', 'like', '%Ceza%')
                    ->orWhere('description', 'like', '%penalty%');
            })
            ->get();

        foreach ($penalties as $penalty) {
            $anomalies->push([
                'type' => 'penalty',
                'order_number' => $penalty->order_number,
                'estimated' => '0.0000',
                'actual' => bcround((string) abs((float) $penalty->amount), self::SCALE),
                'evidence' => ['financial_transaction_id' => $penalty->id, 'description' => $penalty->description],
            ]);
        }

        return $anomalies->values();
    }

    /**
     * @return Collection<int, OrderItemFinancial>
     */
    protected function settledItems(User $user, string $fromYmd, string $toYmd): Collection
    {
        return OrderItemFinancial::query()
            ->whereHas('credential', fn ($q) => $q->where('user_id', $user->id))
            ->whereBetween('order_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->whereIn('source', [ProfitSource::Mixed->value, ProfitSource::Settlement->value])
            ->whereNotNull('estimated_net_profit')
            ->with(['master:id,sku,title', 'order:id,order_number'])
            ->get();
    }

    /**
     * @param  Collection<int, OrderItemFinancial>  $items
     */
    protected function sumColumn(Collection $items, string $column): string
    {
        return bcround(
            $items->reduce(fn (string $carry, OrderItemFinancial $f) => bcadd($carry, (string) ($f->{$column} ?? '0'), 6), '0'),
            self::SCALE
        );
    }

    protected function deviationPct(string $estimated, string $actual): string
    {
        if (bccomp($estimated, '0', 6) === 0) {
            return bccomp($actual, '0', 6) === 0 ? '0.00' : '100.00';
        }

        $diff = bcsub($actual, $estimated, 6);
        $abs = bccomp($estimated, '0', 6) === -1 ? bcmul($estimated, '-1', 6) : $estimated;

        return bcround(bcmul(bcdiv($diff, $abs, 6), '100', 6), 2);
    }

    /**
     * Gerçek, tahmini eşik yüzdesinden fazla aşıyor mu?
     */
    protected function exceedsThreshold(string $estimated, string $actual, string $thresholdPct): bool
    {
        if (bccomp($estimated, '0', 6) <= 0) {
            return false;
        }

        $limit = bcmul($estimated, bcadd('1', bcdiv($thresholdPct, '100', 6), 6), 6);

        return bccomp($actual, $limit, 6) === 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected function anomalyRow(OrderItemFinancial $item, string $type, string $estimated, string $actual): array
    {
        return [
            'type' => $type,
            'order_number' => $item->order->order_number ?? null,
            'sku' => $item->master->sku ?? null,
            'estimated' => bcround($estimated, self::SCALE),
            'actual' => bcround($actual, self::SCALE),
            'evidence' => ['order_item_financial_id' => $item->id],
        ];
    }
}
