<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;

test('para iade ekranı anomali yoksa boş durum gösterir', function () {
    [$user] = userWithTrendyol(); // growth plan → analytics feature açık

    $this->actingAs($user)
        ->get(route('reports.refund-recovery'))
        ->assertOk()
        ->assertSee(__('reports.no_anomalies'));
});

test('para iade ekranı komisyon fazla kesimini kanıtla listeler', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $credential->marketplace_id,
        'user_marketplace_credential_id' => $credential->id,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    OrderItemFinancial::factory()->settled()->create([
        'order_item_id' => $item->id,
        'order_id' => $order->id,
        'user_marketplace_credential_id' => $credential->id,
        'master_product_id' => $master->id,
        'order_date' => now()->toDateString(),
        'estimated_net_profit' => '100.0000',
        'net_profit' => '90.0000',
        'commission' => '20.0000',
        'shipping' => '0.0000',
        'estimate_breakdown' => ['deductions' => ['commission' => '10.0000', 'shipping' => '0']],
    ]);

    $this->actingAs($user)
        ->get(route('reports.refund-recovery'))
        ->assertOk()
        ->assertSee(__('reports.anomaly_commission_overcharge'))
        ->assertSee('order_item_financial_id');
});
