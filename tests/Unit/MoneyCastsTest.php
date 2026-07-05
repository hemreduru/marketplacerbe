<?php

use App\Models\FinancialDailySummary;
use App\Models\OrderItemFinancial;
use App\Models\SubscriptionPayment;

/*
 * "Para kolonları decimal" garantisi (Spec Bölüm 0 / Plan WS-6): para tutan
 * model attribute'ları float değil, decimal cast eder — kuruş güvenliği.
 */

test('kalem kâr defteri para kolonlarını decimal cast eder', function (string $column) {
    $casts = (new OrderItemFinancial)->getCasts();

    expect($casts[$column] ?? '')->toStartWith('decimal:');
})->with([
    'net_revenue', 'cogs', 'commission', 'service_fee', 'shipping',
    'stopaj', 'ad_cost', 'return_cost', 'packaging', 'net_profit',
    'margin', 'estimated_net_profit',
]);

test('günlük özet maliyet/gerçek-kâr kolonlarını decimal cast eder', function (string $column) {
    $casts = (new FinancialDailySummary)->getCasts();

    expect($casts[$column] ?? '')->toStartWith('decimal:');
})->with(['cogs', 'stopaj', 'ad_cost', 'return_cost', 'true_net_profit']);

test('abonelik ödemesi tutarını decimal cast eder', function () {
    expect((new SubscriptionPayment)->getCasts()['amount'] ?? '')->toStartWith('decimal:');
});
