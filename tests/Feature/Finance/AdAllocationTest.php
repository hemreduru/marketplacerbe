<?php

use App\Models\AdCampaign;
use App\Models\AdMetric;
use App\Services\Calculations\AdAllocator;
use App\Services\Finance\AdSpendRepository;

test('totalSpend credential ve dönem bazında reklam harcamasını toplar', function () {
    [, $credential] = userWithTrendyol();

    $campaign = AdCampaign::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'marketplace_code' => 'trendyol',
    ]);

    AdMetric::factory()->create(['ad_campaign_id' => $campaign->id, 'date' => '2026-06-10', 'spend' => '100.0000']);
    AdMetric::factory()->create(['ad_campaign_id' => $campaign->id, 'date' => '2026-06-15', 'spend' => '50.5000']);
    // Dönem dışı — dahil edilmemeli
    AdMetric::factory()->create(['ad_campaign_id' => $campaign->id, 'date' => '2026-07-05', 'spend' => '999.0000']);

    $total = (new AdSpendRepository)->totalSpend($credential->id, 'trendyol', '2026-06-01', '2026-06-30');

    expect($total)->toBe('150.5000');
});

test('totalSpend başka credential veya pazaryerini karıştırmaz', function () {
    [, $credential] = userWithTrendyol();
    [, $other] = userWithTrendyol();

    $mine = AdCampaign::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'marketplace_code' => 'trendyol',
    ]);
    $others = AdCampaign::factory()->create([
        'user_marketplace_credential_id' => $other->id,
        'marketplace_code' => 'trendyol',
    ]);
    $otherMarketplace = AdCampaign::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'marketplace_code' => 'hepsiburada',
    ]);

    AdMetric::factory()->create(['ad_campaign_id' => $mine->id, 'date' => '2026-06-10', 'spend' => '40.0000']);
    AdMetric::factory()->create(['ad_campaign_id' => $others->id, 'date' => '2026-06-10', 'spend' => '77.0000']);
    AdMetric::factory()->create(['ad_campaign_id' => $otherMarketplace->id, 'date' => '2026-06-10', 'spend' => '88.0000']);

    $total = (new AdSpendRepository)->totalSpend($credential->id, 'trendyol', '2026-06-01', '2026-06-30');

    expect($total)->toBe('40.0000');
});

test('blendedPerUnit dönem harcamasını birim başına dağıtır', function () {
    [, $credential] = userWithTrendyol();

    $campaign = AdCampaign::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'marketplace_code' => 'trendyol',
    ]);
    AdMetric::factory()->create(['ad_campaign_id' => $campaign->id, 'date' => '2026-06-10', 'spend' => '300.0000']);

    $allocator = new AdAllocator;

    // 300 TL harcama / 100 toplam birim = 3 TL/birim → 2 birim = 6 TL
    $cost = $allocator->blendedPerUnit($credential->id, 'trendyol', '2026-06-01', '2026-06-30', 2, 100);

    expect($cost)->toBe('6.0000');
});

test('blendedPerUnit toplam birim sıfırsa sıfır döner', function () {
    [, $credential] = userWithTrendyol();

    $allocator = new AdAllocator;

    expect($allocator->blendedPerUnit($credential->id, 'trendyol', '2026-06-01', '2026-06-30', 5, 0))->toBe('0.0000');
});
