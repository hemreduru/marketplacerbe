<?php

namespace App\Services\Marketplaces\Amazon;

use App\Support\ServiceResult;

class FinanceService
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Finances API — list financial event groups (settlement summaries).
     */
    public function listFinancialEventGroups(?string $postedAfter = null): ServiceResult
    {
        $query = [];
        if ($postedAfter) {
            $query['FinancialEventGroupStartedAfter'] = $postedAfter;
        }

        return $this->client->request('GET', '/finances/v0/financialEventGroups', [], $query);
    }

    /**
     * Get financial events for a specific order.
     */
    public function listFinancialEventsByOrderId(string $orderId): ServiceResult
    {
        return $this->client->request('GET', "/finances/v0/orders/{$orderId}/financialEvents");
    }
}
