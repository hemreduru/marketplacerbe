<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Models\Claim;
use App\Support\ServiceResult;
use Carbon\Carbon;

/**
 * Hepsiburada iade/talep senkronizasyonu.
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
        return $this->client->get('/order/api/claims', $filters);
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
                break;
            }

            $content = $result->data['content'] ?? $result->data['data'] ?? [];

            if (empty($content)) {
                break;
            }

            foreach ($content as $item) {
                try {
                    $claim = Claim::updateOrCreate(
                        [
                            'user_marketplace_credential_id' => $credentialId,
                            'remote_id' => (string) ($item['id'] ?? $item['claimNumber'] ?? ''),
                        ],
                        [
                            'order_number' => $item['orderNumber'] ?? null,
                            'status' => $item['status'] ?? 'Created',
                            'customer_name' => $item['customerName'] ?? null,
                            'item_count' => count($item['items'] ?? []),
                            'claim_date' => $this->parseDate($item['createdAt'] ?? $item['claimDate'] ?? null),
                            'raw_data' => $item,
                        ]
                    );

                    $claim->wasRecentlyCreated ? $stats['created']++ : $stats['updated']++;
                } catch (\Exception $e) {
                    $stats['failed']++;
                }
            }

            if ($onProgress) {
                $onProgress($page, null, "Claims page {$page}", $stats);
            }

            $page++;
        } while (count($content) === $size);

        return $stats;
    }

    /**
     * @param  array<int|string>  $claimItemIds
     * @return ServiceResult<array<string, mixed>>
     */
    public function approveClaimItems(string $claimId, array $claimItemIds): ServiceResult
    {
        return $this->client->put('/order/api/claims/'.$claimId.'/approve', [
            'claimLineItemIdList' => $claimItemIds,
        ]);
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
