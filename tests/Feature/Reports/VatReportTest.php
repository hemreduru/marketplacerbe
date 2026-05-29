<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Reports\VatReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('KDV raporu satış KDV ve net yükümlülük hesaplar', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id, 'sku' => 'SKU-VAT', 'vat_rate' => 20, 'cost_price' => 0, 'cost_price_vat_rate' => 20,
    ]);

    $order = Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id, 'order_date' => now()->startOfMonth()->addDay()]);
    // 120 TL KDV dahil satış → 20 TL satış KDV
    OrderItem::factory()->create(['order_id' => $order->id, 'master_product_id' => $master->id, 'quantity' => 1, 'price' => 120, 'commission_amount' => 0, 'shipping_cost' => 0]);

    $service = app(VatReportService::class);
    $report = $service->monthly($user, (int) now()->year, (int) now()->month);

    expect((float) $report['totals']['sale_vat'])->toBe(20.0);
    $row = $report['rows']->firstWhere('sku', 'SKU-VAT');
    expect($row)->not->toBeNull();
});

test('KDV raporu sayfası + CSV export çalışır', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $this->actingAs($user)->get(route('reports.vat'))->assertOk();

    $response = $this->actingAs($user)->get(route('reports.vat.export'));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});
