<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Reports\StockReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('stok raporu kritik filtresi yalnızca eşik altı ürünleri gösterir', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $critical = MasterProduct::factory()->create([
        'user_id' => $user->id, 'sku' => 'SKU-CRIT', 'current_stock' => 2, 'critical_stock_threshold' => 10,
    ]);
    $healthy = MasterProduct::factory()->create([
        'user_id' => $user->id, 'sku' => 'SKU-OK', 'current_stock' => 200, 'critical_stock_threshold' => 10,
    ]);

    $this->actingAs($user)
        ->get(route('reports.stock', ['filter' => 'critical']))
        ->assertOk()
        ->assertSee('SKU-CRIT')
        ->assertDontSee('SKU-OK');
});

test('satış hızı son 30 günden hesaplanır', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create(['user_id' => $user->id, 'current_stock' => 30, 'critical_stock_threshold' => 0]);

    $order = Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id, 'order_date' => now()->subDays(10)]);
    OrderItem::factory()->create(['order_id' => $order->id, 'master_product_id' => $master->id, 'quantity' => 30]);

    $service = app(StockReportService::class);
    $row = $service->rows($user)->firstWhere('id', $master->id);

    // 30 adet / 30 gün = 1.0/gün → 30 stok / 1.0 = 30 gün
    expect($row['velocity'])->toBe(1.0)
        ->and($row['days_to_depletion'])->toBe(30);
});

test('satın alma listesi CSV indirilebilir', function () {
    [$user, $credential] = userWithTrendyol('pro');
    MasterProduct::factory()->create(['user_id' => $user->id, 'current_stock' => 1, 'critical_stock_threshold' => 10]);

    $response = $this->actingAs($user)->get(route('reports.stock.po'));
    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('text/csv');
});
