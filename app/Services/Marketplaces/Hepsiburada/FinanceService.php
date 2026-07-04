<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Models\FinancialDailySummary;
use App\Models\FinancialTransaction;
use App\Services\Finance\DailyProfitAggregator;
use App\Services\Finance\SettlementReconciler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Hepsiburada finansal mutabakat senkronizasyonu.
 *
 * Trendyol FinanceService paterni: settlement satırları FinancialTransaction'a
 * idempotent yazılır, gün bazında FinancialDailySummary üretilir. HB komisyonu
 * KDV dahil satış bedeli üzerinden hesaplanır; satırdaki gerçek tutar esas alınır.
 */
class FinanceService
{
    public function __construct(protected Client $client) {}

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncFinancialData(int $credentialId, string $startDateYmd, string $endDateYmd): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $rows = $this->fetchSettlements($startDateYmd, $endDateYmd);

        $dailyStats = [];

        foreach ($rows as $row) {
            $txDate = $this->parseDate($row['transactionDate'] ?? $row['date'] ?? null);

            if ($txDate === null) {
                $stats['failed']++;

                continue;
            }

            $day = $txDate->format('Y-m-d');
            $type = mb_strtolower((string) ($row['transactionType'] ?? $row['type'] ?? ''), 'UTF-8');
            $order = (string) ($row['orderNumber'] ?? '');
            $isSale = in_array($type, ['sale', 'satış', 'satis', 'order'], true);

            if (! isset($dailyStats[$day])) {
                $dailyStats[$day] = [
                    'gross_sales' => 0, 'commission' => 0, 'shipping_cost' => 0,
                    'platform_expense' => 0, 'other_expense' => 0, 'net_profit' => 0,
                    'order_count' => 0, 'item_count' => 0,
                ];
            }

            if ($isSale) {
                $gross = (float) ($row['credit'] ?? $row['amount'] ?? 0);
                $commission = (float) ($row['commissionAmount'] ?? $row['commission'] ?? 0);

                $tx = FinancialTransaction::updateOrCreate(
                    [
                        'user_marketplace_credential_id' => $credentialId,
                        'transaction_type' => 'Sale',
                        'order_number' => $order,
                        'transaction_date' => $txDate->format('Y-m-d H:i:s'),
                    ],
                    [
                        'amount' => $gross,
                        'commission' => $commission,
                        'description' => 'Sale',
                        'metadata' => $row,
                    ]
                );

                $tx->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;

                $dailyStats[$day]['gross_sales'] += $gross;
                $dailyStats[$day]['commission'] += $commission;
                $dailyStats[$day]['order_count']++;
                $dailyStats[$day]['item_count']++;

                continue;
            }

            // Kesinti satırı: kargo / hizmet bedeli / ceza / diğer
            $desc = (string) ($row['description'] ?? $row['transactionType'] ?? 'Deduction');
            $amt = (float) ($row['debt'] ?? 0) - (float) ($row['credit'] ?? 0);
            if ($amt === 0.0) {
                $amt = abs((float) ($row['amount'] ?? 0));
            }
            $val = max(0, $amt);

            $tx = FinancialTransaction::updateOrCreate(
                [
                    'user_marketplace_credential_id' => $credentialId,
                    'transaction_type' => 'Deduction',
                    'transaction_date' => $txDate->format('Y-m-d H:i:s'),
                    'description' => $desc,
                ],
                [
                    'amount' => -$val,
                    'metadata' => $row,
                ]
            );

            $tx->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;

            $category = $this->classifyDeduction($desc);

            if ($category === 'shipping') {
                $dailyStats[$day]['shipping_cost'] += $val;
            } elseif ($category === 'platform') {
                $dailyStats[$day]['platform_expense'] += $val;
            } else {
                $dailyStats[$day]['other_expense'] += $val;
            }
        }

        foreach ($dailyStats as $day => $s) {
            $s['net_profit'] = $s['gross_sales']
                - $s['commission']
                - $s['shipping_cost']
                - $s['platform_expense']
                - $s['other_expense'];

            // Carbon geçir: 'date' cast'i insert'te 00:00:00'a çevirir; where tarafının
            // aynı formatı görmesi için (SQLite TEXT karşılaştırması) Carbon şart.
            FinancialDailySummary::updateOrCreate(
                [
                    'user_marketplace_credential_id' => $credentialId,
                    'date' => Carbon::parse($day)->startOfDay(),
                ],
                $s
            );
        }

        // Hibrit kâr defteri: settlement komisyonlarını kalemlere işle
        // (HB kargo faturası ayrı endpoint gerektirir — kargo şimdilik tahmin kalır)
        app(SettlementReconciler::class)->reconcileCredential($credentialId, $startDateYmd, $endDateYmd);
        app(DailyProfitAggregator::class)->rebuild($credentialId, $startDateYmd, $endDateYmd);

        return $stats;
    }

    /**
     * Kesinti açıklamasını kategorine ayırır (shipping | platform | other).
     */
    public function classifyDeduction(string $description): string
    {
        $d = mb_strtolower(strtr($description, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');

        foreach (['kargo', 'cargo', 'desi'] as $k) {
            if (str_contains($d, $k)) {
                return 'shipping';
            }
        }

        foreach (['hizmet bedeli', 'işlem bedeli', 'platform', 'komisyon farkı'] as $k) {
            if (str_contains($d, $k)) {
                return 'platform';
            }
        }

        return 'other';
    }

    /**
     * Akıllı artımlı sync: son işlem tarihinden 7 gün geriden başlar,
     * 15 günlük parçalar halinde bugüne kadar ilerler.
     *
     * @param  callable|null  $onProgress  function($current, $total, $msg, $stats)
     */
    public function syncSmart(int $credentialId, ?int $startYear = null, ?callable $onProgress = null): void
    {
        $latestTx = FinancialTransaction::where('user_marketplace_credential_id', $credentialId)
            ->orderBy('transaction_date', 'desc')
            ->first();

        $endDate = Carbon::now();

        if ($startYear) {
            $startDate = Carbon::createFromDate($startYear, 1, 1)->startOfDay();
        } elseif ($latestTx) {
            $startDate = Carbon::parse($latestTx->transaction_date)->subDays(7);
        } else {
            $startDate = Carbon::now()->subYears(2);
        }

        $chunkSize = 15;
        $totalDays = $startDate->diffInDays($endDate);
        $totalChunks = (int) ceil($totalDays / $chunkSize);
        $currentChunkIndex = 0;

        $currentDate = $startDate->copy();

        while ($currentDate->lessThanOrEqualTo($endDate)) {
            $chunkEnd = $currentDate->copy()->addDays($chunkSize)->min($endDate);

            $chunkStats = ['created' => 0, 'updated' => 0, 'failed' => 0];
            try {
                $chunkStats = $this->syncFinancialData(
                    $credentialId,
                    $currentDate->format('Y-m-d'),
                    $chunkEnd->format('Y-m-d')
                );
            } catch (\Exception $e) {
                Log::error("HB chunk sync failed ({$credentialId}): {$currentDate->format('Y-m-d')} - {$e->getMessage()}");
                $chunkStats['failed']++;
            }

            $currentChunkIndex++;
            if ($onProgress) {
                $onProgress(
                    $currentChunkIndex,
                    $totalChunks,
                    'Syncing period: '.$currentDate->format('Y-m-d').' to '.$chunkEnd->format('Y-m-d'),
                    $chunkStats
                );
            }

            $currentDate->addDays($chunkSize + 1);
        }
    }

    /**
     * Settlement satırlarını sayfalayarak toplar.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function fetchSettlements(string $startDateYmd, string $endDateYmd): array
    {
        $pathTemplate = (string) config(
            'marketplaces.hepsiburada.endpoints.settlements',
            '/finances/merchants/{merchantId}/transactions'
        );
        $path = str_replace('{merchantId}', $this->client->getSellerId(), $pathTemplate);

        $all = [];
        $page = 0;
        $size = 100;

        do {
            $result = $this->client->get($path, [
                'startDate' => $startDateYmd,
                'endDate' => $endDateYmd,
                'page' => $page,
                'size' => $size,
            ]);

            if (! $result->ok) {
                Log::error('HB settlement API hatası: '.($result->errorMessage ?? 'bilinmeyen'));

                break;
            }

            $content = $result->data['content'] ?? $result->data['items'] ?? [];
            $all = array_merge($all, $content);
            $page++;
        } while (count($content) === $size);

        return $all;
    }

    protected function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Exception) {
            return null;
        }
    }
}
