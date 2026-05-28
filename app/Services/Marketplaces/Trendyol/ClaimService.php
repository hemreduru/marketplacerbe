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
                    $claim = Claim::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => (string) ($item['id'] ?? ''),
                        ],
                        [
                            'order_number' => $item['orderNumber'] ?? null,
                            'status' => $item['items'][0]['claimItems'][0]['claimItemStatus']['name']
                                ?? ($item['status'] ?? 'Created'),
                            'customer_name' => trim(
                                ($item['customerFirstName'] ?? '').' '.($item['customerLastName'] ?? '')
                            ) ?: null,
                            'item_count' => count($item['items'] ?? []),
                            'claim_date' => isset($item['claimDate'])
                                ? Carbon::createFromTimestampMs($item['claimDate'])
                                : null,
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
