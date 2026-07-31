<?php

use App\Models\FinancialDailySummary;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Services\Finance\DailyProfitAggregator;

test('true_net_profit kalem defterinin günlük toplamına eşittir — dashboard = SKU raporu (K.4)', function () {
    [, $credential] = userWithTrendyol();
    $day = '2026-06-15';

    $order = Order::factory()->create([
        'user_id' => $credential->user_id,
        'marketplace_id' => $credential->marketplace_id,
        'user_marketplace_credential_id' => $credential->id,
        'order_date' => $day,
    ]);

    // Kalem defteri: aynı günde 3 kalem → net_profit toplamı 10 + 20 + (-5) = 25
    foreach (['10.0000', '20.0000', '-5.0000'] as $netProfit) {
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItemFinancial::factory()->create([
            'order_item_id' => $item->id,
            'order_id' => $order->id,
            'user_marketplace_credential_id' => $credential->id,
            'order_date' => $day,
            'net_profit' => $netProfit,
        ]);
    }

    // Settlement özeti günü — keyword kolonları kasıtlı yanıltıcı; kâr formülüne GİRMEMELİ
    FinancialDailySummary::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'date' => $day,
        'gross_sales' => 9999,
        'commission' => 8888,
    ]);

    $updated = app(DailyProfitAggregator::class)->rebuild($credential->id, $day, $day);

    expect($updated)->toBe(1);

    $summary = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)->first();

    // Kalem defteri toplamı = SKU raporu kârı = dashboard günlük kârı (summary'nin
    // 9999/8888 keyword kolonlarından bağımsız)
    expect((string) $summary->true_net_profit)->toBe('25.0000');
});
