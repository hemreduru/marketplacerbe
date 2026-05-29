<?php

use App\Models\Marketplace;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Reports\MarketplaceComparisonService;
use App\Services\Reports\ReportPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('pazaryeri karşılaştırma pivotu SKU başına pazaryeri satışını toplar', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create(['user_id' => $user->id, 'sku' => 'SKU-PIVOT']);

    $hb = Marketplace::where('slug', 'hepsiburada')->first()
        ?? Marketplace::factory()->create(['slug' => 'hepsiburada', 'name' => 'Hepsiburada']);

    $tyOrder = Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $credential->marketplace_id, 'order_date' => now()]);
    OrderItem::factory()->create(['order_id' => $tyOrder->id, 'master_product_id' => $master->id, 'quantity' => 3, 'price' => 100]);

    $hbOrder = Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $hb->id, 'order_date' => now()]);
    OrderItem::factory()->create(['order_id' => $hbOrder->id, 'master_product_id' => $master->id, 'quantity' => 5, 'price' => 100]);

    $service = app(MarketplaceComparisonService::class);
    $pivot = $service->pivot($user, ReportPeriod::fromRequest('this_month'));

    expect($pivot['marketplaces']->pluck('id'))->toContain($credential->marketplace_id, $hb->id);

    $row = $pivot['rows']->firstWhere('sku', 'SKU-PIVOT');
    expect($row['cells'][$credential->marketplace_id]['qty'])->toBe(3)
        ->and($row['cells'][$hb->id]['qty'])->toBe(5);
});

test('pazaryeri karşılaştırma sayfası render olur', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $this->actingAs($user)->get(route('reports.marketplace-comparison'))->assertOk();
});
