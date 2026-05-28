<?php

namespace App\Services\Marketplaces\Amazon;

use App\Support\ServiceResult;

class ReportsService
{
    public function __construct(
        private readonly Client $client,
    ) {}

    /**
     * Reports API — create report request (async).
     */
    public function createReport(string $reportType, array $marketplaceIds = ['A33AVAJ2PDY3EV']): ServiceResult
    {
        return $this->client->request('POST', '/reports/2021-06-30/reports', [
            'reportType' => $reportType,
            'marketplaceIds' => $marketplaceIds,
        ]);
    }

    /**
     * Get report status by reportId.
     */
    public function getReport(string $reportId): ServiceResult
    {
        return $this->client->request('GET', "/reports/2021-06-30/reports/{$reportId}");
    }

    /**
     * Download report document.
     */
    public function getReportDocument(string $reportDocumentId): ServiceResult
    {
        return $this->client->request('GET', "/reports/2021-06-30/documents/{$reportDocumentId}");
    }
}
