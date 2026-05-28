<?php

namespace App\Services\Marketplaces\Pazarama;

class FinanceService
{
    public function __construct(protected Client $client) {}

    public function syncSmart(int $credentialId, ?int $startYear = null, ?callable $onProgress = null): void
    {
        // Pazarama finansal senkronizasyon henuz implemente edilmedi.
    }
}
