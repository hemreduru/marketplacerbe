<?php

use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\Ads\AdReportService;
use App\Services\Ads\AdSyncService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('reklam raporu ROAS ve ACoS doğru hesaplar', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $campaign = AdCampaign::factory()->create(['user_marketplace_credential_id' => $credential->id]);
    AdMetric::factory()->create([
        'ad_campaign_id' => $campaign->id, 'date' => now()->toDateString(),
        'spend' => 100, 'attributed_revenue' => 400,
    ]);

    $report = app(AdReportService::class)->report($user, ReportPeriod::fromRequest('this_month'));

    // ROAS = 400/100 = 4, ACoS = 100/400 = %25
    expect($report['totals']['roas'])->toBe(4.0)
        ->and($report['totals']['acos'])->toBe(25.0);

    $row = $report['campaigns']->first();
    expect($row['profitable'])->toBeTrue();
});

test('reklam sync payload idempotent upsert eder', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $service = app(AdSyncService::class);

    $payload = [[
        'remote_campaign_id' => 'C-1',
        'name' => 'Yaz Kampanyası',
        'metrics' => [['date' => now()->toDateString(), 'spend' => 50, 'attributed_revenue' => 200]],
    ]];

    $service->syncFromPayload($credential, $payload);
    $service->syncFromPayload($credential, $payload); // ikinci kez

    expect(AdCampaign::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1)
        ->and(AdMetric::count())->toBe(1);
});

test('reklam raporu sayfası render olur', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $this->actingAs($user)->get(route('reports.ads'))->assertOk();
});
