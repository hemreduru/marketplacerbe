<?php

namespace App\Services\Marketplaces\N11;

class FinanceService
{
    public function __construct(protected Client $client) {}

    public function syncSmart(int $credentialId, ?int $startYear = null, ?callable $onProgress = null): void
    {
        // N11 finansal senkronizasyon henuz implemente edilmedi.
    }
}
