<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Models\UserMarketplaceCredential;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\DB;

/**
 * PR 4.6 — Reklam verisi senkronizasyonu (Trendyol Ads / HB Sponsor).
 *
 * Canlı API endpoint'leri sandbox erişimi gerektirir (ileri PR); bu servis
 * idempotent upsert mantığını içerir ve payload'dan beslenebilir. {@see syncFromPayload}
 * test ile doğrulanır; {@see sync} ince HTTP sarmalayıcısıdır (endpoint config gerekir).
 */
class AdSyncService
{
    /**
     * Pazaryeri reklam API'sinden çek (scaffold — endpoint config gerektirir).
     *
     * @return ServiceResult<array<string, int>>
     */
    public function sync(UserMarketplaceCredential $credential): ServiceResult
    {
        $code = $credential->marketplace?->slug ?? 'trendyol';
        $endpoint = config("marketplaces.{$code}.ads.campaigns_endpoint");

        if (empty($endpoint)) {
            return ServiceResult::fail('ads_endpoint_not_configured', __('reports.ads_not_configured'));
        }

        // Canlı entegrasyon ileri PR'da; yapı hazır. Şimdilik boş payload güvenli döner.
        return $this->syncFromPayload($credential, []);
    }

    /**
     * Normalize edilmiş payload'dan kampanya + metrikleri idempotent upsert eder.
     *
     * @param  array<int, array{remote_campaign_id: string, name: string, status?: string, metrics?: array<int, array{date: string, spend?: float|string, attributed_revenue?: float|string, impressions?: int, clicks?: int, orders?: int}>}>  $campaigns
     * @return ServiceResult<array<string, int>>
     */
    public function syncFromPayload(UserMarketplaceCredential $credential, array $campaigns): ServiceResult
    {
        $code = $credential->marketplace?->slug ?? 'trendyol';
        $campaignCount = 0;
        $metricCount = 0;

        DB::transaction(function () use ($credential, $code, $campaigns, &$campaignCount, &$metricCount) {
            foreach ($campaigns as $c) {
                $campaign = AdCampaign::updateOrCreate(
                    [
                        'user_marketplace_credential_id' => $credential->id,
                        'remote_campaign_id' => (string) $c['remote_campaign_id'],
                    ],
                    [
                        'marketplace_code' => $code,
                        'name' => $c['name'] ?? 'Campaign',
                        'status' => $c['status'] ?? 'active',
                    ],
                );
                $campaignCount++;

                foreach ($c['metrics'] ?? [] as $m) {
                    AdMetric::updateOrCreate(
                        ['ad_campaign_id' => $campaign->id, 'date' => $m['date']],
                        [
                            'spend' => $m['spend'] ?? 0,
                            'attributed_revenue' => $m['attributed_revenue'] ?? 0,
                            'impressions' => $m['impressions'] ?? 0,
                            'clicks' => $m['clicks'] ?? 0,
                            'orders' => $m['orders'] ?? 0,
                        ],
                    );
                    $metricCount++;
                }
            }
        });

        return ServiceResult::ok(['campaigns' => $campaignCount, 'metrics' => $metricCount]);
    }
}
