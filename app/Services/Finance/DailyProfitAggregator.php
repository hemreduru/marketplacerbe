<?php

namespace App\Services\Finance;

use App\Models\FinancialDailySummary;
use App\Models\OrderItemFinancial;
use Illuminate\Support\Facades\DB;

/**
 * Kalem bazlı kâr defterini günlük özete toplar — COGS dashboard'a buradan girer.
 *
 * Mevcut FinancialDailySummary satırlarını (settlement günleri) COGS, stopaj,
 * reklam ve iade maliyetleriyle zenginleştirir ve GERÇEK net kârı yazar.
 *
 *   true_net_profit = SUM(order_item_financials.net_profit)
 *
 * Kalem defteri (order_item_financials) tek doğruluk kaynağıdır; böylece
 * dashboard günlük kârı SKU raporu kârına birebir eşittir. Summary'nin
 * keyword-kategorize commission/shipping kolonları yalnızca gider kırılımı
 * (donut) içindir, kâr formülüne GİRMEZ.
 *
 * Yalnızca settlement özeti OLAN günler işlenir; tahmin-yalnız günler
 * dashboard'a Stage 5'te ProfitAggregator overlay'i ile eklenir.
 */
class DailyProfitAggregator
{
    private const SCALE = 4;

    public function rebuild(int $credentialId, string $fromYmd, string $toYmd): int
    {
        // DATE() ile grupla: order_date'in TEXT saklama formatı (SQLite
        // '00:00:00' ekli olabilir) dialect'ten bağımsız normalize edilir.
        $itemTotals = OrderItemFinancial::query()
            ->where('user_marketplace_credential_id', $credentialId)
            ->whereBetween('order_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->groupBy(DB::raw('DATE(order_date)'))
            ->select([
                DB::raw('DATE(order_date) as day'),
                DB::raw('SUM(cogs) as cogs_sum'),
                DB::raw('SUM(stopaj) as stopaj_sum'),
                DB::raw('SUM(ad_cost) as ad_cost_sum'),
                DB::raw('SUM(return_cost) as return_cost_sum'),
                DB::raw('SUM(net_profit) as net_profit_sum'),
            ])
            ->get()
            ->keyBy('day');

        $summaries = FinancialDailySummary::query()
            ->where('user_marketplace_credential_id', $credentialId)
            ->whereBetween('date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->get();

        $updated = 0;

        foreach ($summaries as $summary) {
            $day = $summary->date->format('Y-m-d');
            $totals = $itemTotals->get($day);

            $cogs = bcround((string) ($totals->cogs_sum ?? '0'), self::SCALE);
            $stopaj = bcround((string) ($totals->stopaj_sum ?? '0'), self::SCALE);
            $adCost = bcround((string) ($totals->ad_cost_sum ?? '0'), self::SCALE);
            $returnCost = bcround((string) ($totals->return_cost_sum ?? '0'), self::SCALE);

            // K.4: gerçek net kâr = kalem defterinin günlük net_profit toplamı
            // (tek doğruluk kaynağı). Summary'nin keyword komisyon/kargo kolonları
            // artık kâr formülüne girmez — yalnızca gider kırılımı için tutulur.
            $trueNet = bcround((string) ($totals->net_profit_sum ?? '0'), self::SCALE);

            $summary->update([
                'cogs' => $cogs,
                'stopaj' => $stopaj,
                'ad_cost' => $adCost,
                'return_cost' => $returnCost,
                'true_net_profit' => $trueNet,
            ]);

            $updated++;
        }

        return $updated;
    }
}
