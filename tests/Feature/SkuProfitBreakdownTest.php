<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;

test('sku-profit raporu kesinti kırılımını (komisyon/kargo/stopaj/reklam) gösterir (K.6)', function () {
    [$user, $credential] = userWithTrendyol(); // growth → analytics feature açık

    $master = MasterProduct::factory()->create([
        'user_id' => $user->id, 'sku' => 'SKU-BRK', 'title' => 'Kirilim Urun',
    ]);
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'user_marketplace_credential_id' => $credential->id,
        'marketplace_id' => $credential->marketplace_id,
        'order_date' => now(),
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);
    OrderItemFinancial::factory()->create([
        'order_item_id' => $item->id,
        'order_id' => $order->id,
        'user_marketplace_credential_id' => $credential->id,
        'master_product_id' => $master->id,
        'order_date' => now(),
        'net_revenue' => '166.6700',
        'commission' => '25.0000',
        'shipping' => '12.3400',
        'stopaj' => '1.6700',
        'ad_cost' => '3.0000',
        'return_cost' => '0.0000',
        'net_profit' => '100.0000',
    ]);

    $this->actingAs($user)
        ->get(route('reports.sku-profit', ['period' => 'this_month']))
        ->assertOk()
        ->assertSee(__('reports.commission'))
        ->assertSee(__('reports.shipping'))
        ->assertSee(__('reports.stopaj'))
        ->assertSee(__('reports.ad_cost'))
        ->assertSee('25.00')   // komisyon kolonundaki değer
        ->assertSee('12.34');  // kargo kolonundaki değer
});
