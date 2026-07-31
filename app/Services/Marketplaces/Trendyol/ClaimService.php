<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Models\Claim;
use App\Support\ServiceResult;
use Carbon\Carbon;

/**
 * Trendyol iade/talep senkronizasyonu.
 */
class ClaimService
{
    public function __construct(protected Client $client) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return ServiceResult<array<string, mixed>>
     */
    public function getClaims(array $filters = []): ServiceResult
    {
        $path = '/integration/order/sellers/'.$this->client->getSellerId().'/claims';

        return $this->client->get($path, $filters);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncClaims(int $credentialId, ?callable $onProgress = null): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'failed' => 0];
        $page = 0;
        $size = 50;

        do {
            $result = $this->getClaims(['page' => $page, 'size' => $size]);

            if (! $result->ok) {
                throw new \RuntimeException($result->errorMessage ?? 'Trendyol claim API hatası');
            }

            $content = $result->data['content'] ?? [];
            $totalPages = (int) ($result->data['totalPages'] ?? 1);

            foreach ($content as $item) {
                try {
                    $status = $item['items'][0]['claimItems'][0]['claimItemStatus']['name']
                        ?? ($item['status'] ?? 'Created');
                    $claimDate = isset($item['claimDate'])
                        ? Carbon::createFromTimestampMs($item['claimDate'])
                        : null;

                    $claim = Claim::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => (string) ($item['id'] ?? ''),
                        ],
                        [
                            'order_number' => $item['orderNumber'] ?? null,
                            'status' => $status,
                            'customer_name' => trim(
                                ($item['customerFirstName'] ?? '').' '.($item['customerLastName'] ?? '')
                            ) ?: null,
                            'item_count' => count($item['items'] ?? []),
                            'claim_date' => $claimDate,
                            'refund_amount' => $this->extractRefundAmount($item),
                            // İade "Accepted" ise onay tarihi = claim_date; ReturnCostResolver
                            // yalnızca approved_at dolu iadeleri iade maliyetine katar (K.2).
                            'approved_at' => $status === 'Accepted' ? $claimDate : null,
                            'raw_data' => $item,
                        ]
                    );

                    $claim->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                }
            }

            if ($onProgress) {
                $onProgress($page, $totalPages, "Claims page {$page}", $stats);
            }

            $page++;
        } while ($page < $totalPages);

        return $stats;
    }

    /**
     * Talep kalemlerinden toplam iade tutarı: her claimItem bir iade birimi,
     * birim fiyat order line'dan gelir.
     *
     * @param  array<string, mixed>  $item
     */
    private function extractRefundAmount(array $item): string
    {
        $total = '0.0000';
        foreach ($item['items'] ?? [] as $line) {
            $unitPrice = (string) ($line['orderLine']['price'] ?? 0);
            $count = count($line['claimItems'] ?? []);
            $total = bcadd($total, bcmul($unitPrice, (string) $count, 4), 4);
        }

        return $total;
    }

    /**
     * @param  array<int|string>  $claimItemIds
     * @return ServiceResult<array<string, mixed>>
     */
    public function approveClaimItems(string $claimId, array $claimItemIds): ServiceResult
    {
        $path = '/integration/order/sellers/'.$this->client->getSellerId()
            .'/claims/'.$claimId.'/items/approve';

        return $this->client->put($path, ['claimLineItemIdList' => $claimItemIds]);
    }
}
