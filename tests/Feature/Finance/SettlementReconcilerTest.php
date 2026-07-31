<?php

use App\Models\Claim;
use App\Models\FinancialDailySummary;
use App\Models\FinancialTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Models\UserMarketplaceCredential;
use App\Services\Finance\DailyProfitAggregator;
use App\Services\Finance\SettlementReconciler;
use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;

/**
 * @return array{0: UserMarketplaceCredential, 1: Order, 2: array<int, OrderItemFinancial>}
 */
function reconcilerSetup(string $orderDate, array $netRevenues = ['100.0000', '50.0000']): array
{
    [, $credential] = userWithTrendyol();

    $order = Order::factory()->create([
        'user_id' => $credential->user_id,
        'marketplace_id' => $credential->marketplace_id,
        'user_marketplace_credential_id' => $credential->id,
        'order_number' => 'TY-'.fake()->unique()->numberBetween(1000, 99999),
        'order_date' => $orderDate,
    ]);

    $financials = [];
    foreach ($netRevenues as $netRevenue) {
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        $financials[] = OrderItemFinancial::factory()->create([
            'order_item_id' => $item->id,
            'order_id' => $order->id,
            'user_marketplace_credential_id' => $credential->id,
            'order_date' => $orderDate,
            'net_revenue' => $netRevenue,
            'commission' => '15.0000',
            'shipping' => '40.0000',
        ]);
    }

    return [$credential, $order, $financials];
}

test('settlement komisyonu kalemlere net gelir payıyla kayıpsız dağıtılır', function () {
    [$credential, $order] = reconcilerSetup(now()->toDateString());

    // Gerçek komisyon 30.01 TL — tahmindeki 15+15'ten farklı, tam bölünmeyen tutar
    FinancialTransaction::create([
        'user_marketplace_credential_id' => $credential->id,
        'transaction_type' => 'Sale',
        'order_number' => $order->order_number,
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'amount' => 180.00,
        'commission' => 30.01,
        'description' => 'Sale',
        'metadata' => [],
    ]);

    app(SettlementReconciler::class)->reconcileCredential(
        $credential->id,
        now()->subDay()->toDateString(),
        now()->addDay()->toDateString(),
    );

    $financials = OrderItemFinancial::where('order_id', $order->id)->orderBy('id')->get();

    $sum = bcadd((string) $financials[0]->commission, (string) $financials[1]->commission, 4);
    expect($sum)->toBe('30.0100')
        // 100/150 pay → 20.0067 + artık; 50/150 pay → 10.0033
        ->and((string) $financials[0]->commission)->toBe('20.0067')
        ->and((string) $financials[1]->commission)->toBe('10.0033')
        ->and($financials[0]->component_sources['commission'])->toBe('settlement')
        ->and($financials[0]->reconciliation_status)->toBe(ReconciliationStatus::PartiallySettled)
        ->and($financials[0]->source)->toBe(ProfitSource::Mixed);
});

test('komisyon + kargo settlement ve pencere kapalıysa kalem settled olur', function () {
    // 20 gün önceki sipariş → 15 günlük iade penceresi kapalı
    [$credential, $order] = reconcilerSetup(now()->subDays(20)->toDateString(), ['100.0000']);

    FinancialTransaction::create([
        'user_marketplace_credential_id' => $credential->id,
        'transaction_type' => 'Sale',
        'order_number' => $order->order_number,
        'transaction_date' => now()->subDays(18)->format('Y-m-d H:i:s'),
        'amount' => 120.00,
        'commission' => 18.00,
        'description' => 'Sale',
        'metadata' => [],
    ]);

    app(SettlementReconciler::class)->reconcileCredential(
        $credential->id,
        now()->subDays(30)->toDateString(),
        now()->toDateString(),
        [$order->order_number => 47.50], // gerçek kargo faturası
    );

    $financial = OrderItemFinancial::where('order_id', $order->id)->first();

    expect((string) $financial->commission)->toBe('18.0000')
        ->and((string) $financial->shipping)->toBe('47.5000')
        ->and($financial->source)->toBe(ProfitSource::Settlement)
        ->and($financial->reconciliation_status)->toBe(ReconciliationStatus::Settled)
        ->and($financial->reconciled_at)->not->toBeNull();

    // net_profit kaynaktan yeniden hesaplanmış olmalı:
    // 100 - (40 cogs + 18 komisyon + 8.49 fee + 47.5 kargo + 1 stopaj + 0 + 0 + 0) = -14.99
    expect((string) $financial->net_profit)->toBe('-14.9900');
});

test('mutabakat idempotenttir — ikinci koşu sonucu değiştirmez', function () {
    [$credential, $order] = reconcilerSetup(now()->toDateString(), ['100.0000']);

    FinancialTransaction::create([
        'user_marketplace_credential_id' => $credential->id,
        'transaction_type' => 'Sale',
        'order_number' => $order->order_number,
        'transaction_date' => now()->format('Y-m-d H:i:s'),
        'amount' => 120.00,
        'commission' => 21.00,
        'description' => 'Sale',
        'metadata' => [],
    ]);

    $reconciler = app(SettlementReconciler::class);
    $from = now()->subDay()->toDateString();
    $to = now()->addDay()->toDateString();

    $reconciler->reconcileCredential($credential->id, $from, $to);
    $first = OrderItemFinancial::where('order_id', $order->id)->first()->only(['commission', 'net_profit']);

    $reconciler->reconcileCredential($credential->id, $from, $to);
    $second = OrderItemFinancial::where('order_id', $order->id)->first()->only(['commission', 'net_profit']);

    expect($second)->toBe($first);
});

test('DailyProfitAggregator günlük özete COGS dahil gerçek net kârı yazar', function () {
    $day = now()->subDays(3)->toDateString();
    [$credential] = reconcilerSetup($day, ['100.0000', '50.0000']);

    // Settlement günü özeti (legacy alanlar)
    FinancialDailySummary::create([
        'user_marketplace_credential_id' => $credential->id,
        'date' => $day,
        'gross_sales' => 180.00,
        'commission' => 27.00,
        'shipping_cost' => 40.00,
        'platform_expense' => 8.49,
        'other_expense' => 0,
        'net_profit' => 104.51,
        'order_count' => 1,
        'item_count' => 2,
    ]);

    $updated = app(DailyProfitAggregator::class)->rebuild(
        $credential->id,
        now()->subDays(5)->toDateString(),
        now()->toDateString(),
    );

    expect($updated)->toBe(1);

    $summary = FinancialDailySummary::where('user_marketplace_credential_id', $credential->id)->first();

    // Factory: kalem başına cogs 40, stopaj 1 → 2 kalem: cogs 80, stopaj 2
    expect((string) $summary->cogs)->toBe('80.0000')
        ->and((string) $summary->stopaj)->toBe('2.0000')
        // K.4: true_net = SUM(kalem net_profit) = 2 × -4.4900 = -8.9800 (kalem defteri tek kaynak,
        // summary'nin keyword komisyon/kargo kolonları artık kâr formülüne girmez)
        ->and((string) $summary->true_net_profit)->toBe('-8.9800')
        // legacy net_profit dokunulmadı
        ->and((float) $summary->net_profit)->toBe(104.51);
});

test('onaylı iade kalemin return_cost alanına işlenir ve statü reopened_return olur', function () {
    [$credential, $order] = reconcilerSetup(now()->subDays(2)->toDateString(), ['100.0000']);

    Claim::create([
        'user_marketplace_credential_id' => $credential->id,
        'remote_id' => 'CLM-99',
        'order_number' => $order->order_number,
        'status' => 'Approved',
        'item_count' => 1,
        'claim_date' => now()->subDay(),
        'approved_at' => now(),
        'refund_amount' => '35.0000',
    ]);

    app(SettlementReconciler::class)->reconcileCredential(
        $credential->id,
        now()->subDays(5)->toDateString(),
        now()->toDateString(),
    );

    $financial = OrderItemFinancial::where('order_id', $order->id)->first();

    expect((string) $financial->return_cost)->toBe('35.0000')
        ->and($financial->component_sources['return_cost'])->toBe('settlement')
        // pencere (15g) hâlâ açık → reopened_return
        ->and($financial->reconciliation_status)->toBe(ReconciliationStatus::ReopenedReturn);
});
