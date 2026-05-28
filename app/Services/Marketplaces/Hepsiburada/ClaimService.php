<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Support\ServiceResult;

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

        do {
            $result = $this->getClaims(['page' => $page, 'size' => 50]);

            if (! $result->ok) {
                break;
            }

            $content = $result->data['content'] ?? $result->data ?? [];
            if (empty($content)) {
                break;
            }

            $page++;
        } while (! empty($content));

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
}
