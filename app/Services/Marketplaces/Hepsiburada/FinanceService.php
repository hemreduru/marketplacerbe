<?php

namespace App\Services\Marketplaces\Hepsiburada;

class FinanceService
{
    public function __construct(protected Client $client) {}

    public function syncSmart(int $credentialId, ?int $startYear = null, ?callable $onProgress = null): void
    {
        // Hepsiburada finansal senkronizasyon henuz implemente edilmedi.
    }
}
