<?php

namespace App\Services\Contracts;

interface ClaimServiceContract
{
    /**
     * Fetch a page of return claims from the marketplace.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function getClaims(array $filters = []): array;

    /**
     * Pull return claims for the given credential into local storage.
     *
     * @return array{created: int, updated: int, failed: int}
     */
    public function syncClaims(int $credentialId, ?callable $onProgress = null): array;

    /**
     * Approve claim line items on the marketplace (a live write operation).
     *
     * @param  array<int|string>  $claimItemIds
     * @return array<string, mixed>
     */
    public function approveClaimItems(string $claimId, array $claimItemIds): array;
}
