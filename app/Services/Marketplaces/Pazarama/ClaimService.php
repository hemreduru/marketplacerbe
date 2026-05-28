<?php

namespace App\Services\Marketplaces\Pazarama;

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
        return $this->client->get('/claims', $filters);
    }

    /**
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncClaims(int $credentialId, ?callable $onProgress = null): array
    {
        return ['created' => 0, 'updated' => 0, 'failed' => 0];
    }

    /**
     * @param  array<int|string>  $claimItemIds
     * @return ServiceResult<array<string, mixed>>
     */
    public function approveClaimItems(string $claimId, array $claimItemIds): ServiceResult
    {
        return ServiceResult::fail('not_implemented', 'Pazarama claim onayi henuz implemente edilmedi.');
    }
}
