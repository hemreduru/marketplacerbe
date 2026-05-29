<?php

use App\Models\Claim;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Reports\ReportPeriod;
use App\Services\Reports\ReturnReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('iade analizi özeti satış/iade adedi ve oranı hesaplar', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);

    $order = Order::factory()->create([
        'user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id,
        'order_number' => 'ORD-RET-1', 'order_date' => now(),
    ]);
    OrderItem::factory()->create(['order_id' => $order->id, 'master_product_id' => $master->id, 'quantity' => 10]);

    Claim::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'order_number' => 'ORD-RET-1',
        'return_reason' => 'size_issue',
        'item_count' => 2,
        'refund_amount' => 50,
        'claim_date' => now(),
    ]);

    $service = app(ReturnReportService::class);
    $period = ReportPeriod::fromRequest('this_month');
    $summary = $service->summary($user, $period);

    expect($summary['sales_qty'])->toBe(10)
        ->and($summary['return_qty'])->toBe(2)
        ->and($summary['return_rate'])->toBe(20.0);

    $bySku = $service->bySku($user, $period);
    expect($bySku->first()['return_rate'])->toBe(20.0);
});

test('iade analiz raporu sayfası render olur', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $this->actingAs($user)->get(route('reports.returns'))->assertOk();
});
