<?php

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Support\Enums\ReconciliationStatus;

test('dashboard net kâr kartı kaynak rozetini gösterir (K.7)', function (array $statuses, string $expectedKey) {
    [$user, $credential] = userWithTrendyol();

    $order = Order::factory()->create([
        'user_id' => $user->id,
        'user_marketplace_credential_id' => $credential->id,
        'marketplace_id' => $credential->marketplace_id,
        'order_date' => now(),
    ]);

    foreach ($statuses as $status) {
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItemFinancial::factory()->create([
            'order_item_id' => $item->id,
            'order_id' => $order->id,
            'user_marketplace_credential_id' => $credential->id,
            'order_date' => now(),
            'reconciliation_status' => $status,
        ]);
    }

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertSee(__("dashboard.$expectedKey"));
})->with([
    'hepsi settled → gerçek' => [[ReconciliationStatus::Settled, ReconciliationStatus::Settled], 'source_settled'],
    'hepsi estimated → tahmini' => [[ReconciliationStatus::Estimated], 'source_estimate'],
    'karışık → kısmi' => [[ReconciliationStatus::Settled, ReconciliationStatus::Estimated], 'source_mixed'],
]);

test('veri yoksa kaynak rozeti gösterilmez (K.7)', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('dashboard.source_estimate'))
        ->assertDontSee(__('dashboard.source_settled'));
});
