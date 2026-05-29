<?php

use App\Models\Order;
use App\Services\Reports\AnalyticsReportService;
use App\Services\Reports\ReportPeriod;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('en çok satış şehirleri sipariş sayısına göre sıralanır', function () {
    [$user, $credential] = userWithTrendyol('pro');

    Order::factory()->count(3)->create(['user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id, 'order_date' => now(), 'shipping_city' => 'Istanbul']);
    Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id, 'order_date' => now(), 'shipping_city' => 'Ankara']);

    $cities = app(AnalyticsReportService::class)->topCities($user, ReportPeriod::fromRequest('this_month'));

    expect($cities->first()['city'])->toBe('Istanbul')
        ->and($cities->first()['orders'])->toBe(3);
});

test('saat ısı haritası siparişi doğru gün/saat hücresine yerleştirir', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $when = now()->startOfMonth()->next(Carbon::MONDAY)->setTime(14, 30);
    Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id, 'order_date' => $when]);

    $matrix = app(AnalyticsReportService::class)->hourlyHeatmap($user, ReportPeriod::fromRequest('this_month'));

    expect($matrix[$when->isoWeekday()][14])->toBe(1);
});

test('analitik raporu sayfası render olur', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $this->actingAs($user)->get(route('reports.analytics'))->assertOk();
});
