<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Models\FinancialDailySummary;
use App\Models\FinancialTransaction;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FinanceService
{
    public function __construct(protected Client $client) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchCHEChunked(string $resource, array $query): array
    {
        $startMs = (int) $query['startDate'];
        $endMs = (int) $query['endDate'];
        $type = $query['transactionType'];
        $size = (int) ($query['size'] ?? 1000);
        $maxSpan = 15 * 24 * 60 * 60 * 1000;

        $cursor = $startMs;
        $all = [];

        while ($cursor <= $endMs) {
            $chunkEnd = min($endMs, $cursor + $maxSpan - 1000);
            $page = 0;

            do {
                $path = '/integration/finance/che/sellers/'
                    .$this->client->getSellerId()
                    .'/'.$resource;

                $result = $this->client->get($path, [
                    'startDate' => $cursor,
                    'endDate' => $chunkEnd,
                    'transactionType' => $type,
                    'page' => $page,
                    'size' => $size,
                ]);

                if (! $result->ok) {
                    Log::error("Trendyol API Error ({$resource}): {$result->errorMessage}");

                    break;
                }

                $data = $result->data;
                $items = $data['content'] ?? [];
                $all = array_merge($all, $items);
                $totalPages = (int) ($data['totalPages'] ?? 1);
                $page++;

            } while ($page < $totalPages);

            $cursor = $chunkEnd + 1000;
        }

        return $all;
    }

    /**
     * @param  array<int, array<string, mixed>>  $deductionRecords
     * @return array<string, float>
     */
    public function fetchCargoInvoiceMap(array $deductionRecords): array
    {
        $orderShipMap = [];
        $seenInvoices = [];

        foreach ($deductionRecords as $d) {
            $txType = (string) ($d['transactionType'] ?? '');
            $id = (string) ($d['id'] ?? '');

            if ($id === '') {
                continue;
            }

            $t = mb_strtolower($txType, 'UTF-8');
            if ($t !== 'kargo faturası' && $t !== 'kargo fatura') {
                continue;
            }

            if (isset($seenInvoices[$id])) {
                continue;
            }
            $seenInvoices[$id] = true;

            $path = '/integration/finance/che/sellers/'
                .$this->client->getSellerId()
                .'/cargo-invoice/'.$id.'/items';

            $result = $this->client->get($path);

            if (! $result->ok) {
                Log::error("Trendyol Cargo Invoice Error ({$id}): {$result->errorMessage}");

                continue;
            }

            $data = $result->data;
            foreach (($data['content'] ?? []) as $item) {
                $order = (string) ($item['orderNumber'] ?? '');
                $amt = (float) ($item['amount'] ?? 0);

                if ($order === '') {
                    continue;
                }
                if (! isset($orderShipMap[$order])) {
                    $orderShipMap[$order] = 0.0;
                }
                $orderShipMap[$order] += $amt;
            }
        }

        return $orderShipMap;
    }

    public function classifyDeduction(string $description): string
    {
        $d = mb_strtolower(strtr($description, ['I' => 'ı', 'İ' => 'i']), 'UTF-8');

        $platform = ['platform hizmet', 'platform hizmeti', 'hizmet bedeli', 'platform bedeli', 'phb', 'p.h.b'];
        foreach ($platform as $k) {
            if (strpos($d, $k) !== false) {
                return 'platform';
            }
        }

        $intlOps = ['yurtdışı operasyon', 'yurt dışı operasyon', 'yd operasyon'];
        foreach ($intlOps as $k) {
            if (strpos($d, $k) !== false) {
                return 'intl_ops';
            }
        }

        $intlSrv = ['uluslararası hizmet', 'international service'];
        foreach ($intlSrv as $k) {
            if (strpos($d, $k) !== false) {
                return 'intl_service';
            }
        }

        $pen = ['ceza', 'penalty'];
        foreach ($pen as $k) {
            if (strpos($d, $k) !== false) {
                return 'penalty';
            }
        }

        return 'other';
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncFinancialData(int $credentialId, string $startDateYmd, string $endDateYmd): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $startMs = strtotime($startDateYmd.' 00:00:00') * 1000;
        $endMs = strtotime($endDateYmd.' 23:59:59') * 1000;

        $sales = $this->fetchCHEChunked('settlements', [
            'transactionType' => 'Sale',
            'startDate' => $startMs,
            'endDate' => $endMs,
            'size' => 1000,
        ]);

        $deductions = $this->fetchCHEChunked('otherfinancials', [
            'transactionType' => 'DeductionInvoices',
            'startDate' => $startMs,
            'endDate' => $endMs,
            'size' => 1000,
        ]);

        $dailyStats = [];

        foreach ($sales as $s) {
            $txDateMs = (int) ($s['transactionDate'] ?? 0);
            $txDate = date('Y-m-d H:i:s', intdiv($txDateMs, 1000));
            $day = date('Y-m-d', intdiv($txDateMs, 1000));
            $order = (string) ($s['orderNumber'] ?? '');
            $gross = (float) ($s['credit'] ?? 0);
            $commission = (float) ($s['commissionAmount'] ?? 0);

            $tx = FinancialTransaction::updateOrCreate(
                [
                    'user_marketplace_credential_id' => $credentialId,
                    'transaction_type' => 'Sale',
                    'order_number' => $order,
                    'transaction_date' => $txDate,
                ],
                [
                    'amount' => $gross,
                    'commission' => $commission,
                    'description' => 'Sale',
                    'metadata' => $s,
                ]
            );

            if ($tx->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            if (! isset($dailyStats[$day])) {
                $dailyStats[$day] = [
                    'gross_sales' => 0, 'commission' => 0, 'shipping_cost' => 0,
                    'platform_expense' => 0, 'other_expense' => 0, 'net_profit' => 0,
                    'order_count' => 0, 'item_count' => 0,
                ];
            }
            $dailyStats[$day]['gross_sales'] += $gross;
            $dailyStats[$day]['commission'] += $commission;
            $dailyStats[$day]['order_count']++;
            $dailyStats[$day]['item_count']++;
        }

        foreach ($deductions as $d) {
            $txDateMs = (int) ($d['transactionDate'] ?? 0);
            $txDate = date('Y-m-d H:i:s', intdiv($txDateMs, 1000));
            $day = date('Y-m-d', intdiv($txDateMs, 1000));
            $desc = (string) ($d['description'] ?? '');
            $cat = $this->classifyDeduction($desc);
            $amt = (float) ($d['debt'] ?? 0) - (float) ($d['credit'] ?? 0);
            $val = max(0, $amt);

            $tx = FinancialTransaction::updateOrCreate(
                [
                    'user_marketplace_credential_id' => $credentialId,
                    'transaction_type' => 'Deduction',
                    'transaction_date' => $txDate,
                    'description' => $desc,
                ],
                [
                    'amount' => -$val,
                    'metadata' => $d,
                ]
            );

            if ($tx->wasRecentlyCreated) {
                $stats['created']++;
            } else {
                $stats['updated']++;
            }

            if (! isset($dailyStats[$day])) {
                $dailyStats[$day] = [
                    'gross_sales' => 0, 'commission' => 0, 'shipping_cost' => 0,
                    'platform_expense' => 0, 'other_expense' => 0, 'net_profit' => 0,
                    'order_count' => 0, 'item_count' => 0,
                ];
            }

            if ($cat === 'platform') {
                $dailyStats[$day]['platform_expense'] += $val;
            } else {
                $dailyStats[$day]['other_expense'] += $val;
            }
        }

        foreach ($deductions as $d) {
            $txDateMs = (int) ($d['transactionDate'] ?? 0);
            $day = date('Y-m-d', intdiv($txDateMs, 1000));
            $txType = (string) ($d['transactionType'] ?? '');
            $t = mb_strtolower($txType, 'UTF-8');

            $amt = (float) ($d['debt'] ?? 0) - (float) ($d['credit'] ?? 0);
            $val = max(0, $amt);

            if ($t === 'kargo faturası' || $t === 'kargo fatura') {
                if (! isset($dailyStats[$day])) {
                    $dailyStats[$day] = [
                        'gross_sales' => 0, 'commission' => 0, 'shipping_cost' => 0,
                        'platform_expense' => 0, 'other_expense' => 0, 'net_profit' => 0,
                        'order_count' => 0, 'item_count' => 0,
                    ];
                }
                $dailyStats[$day]['shipping_cost'] += $val;
            }
        }

        foreach ($dailyStats as $day => $s) {
            $s['net_profit'] = $s['gross_sales']
                - $s['commission']
                - $s['shipping_cost']
                - $s['platform_expense']
                - $s['other_expense'];

            FinancialDailySummary::updateOrCreate(
                [
                    'user_marketplace_credential_id' => $credentialId,
                    'date' => $day,
                ],
                $s
            );
        }

        return $stats;
    }

    /**
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
            $startDate = Carbon::now()->subYears(5);
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
                Log::error("Chunk sync failed ({$credentialId}): {$currentDate->format('Y-m-d')} - {$e->getMessage()}");
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
}
