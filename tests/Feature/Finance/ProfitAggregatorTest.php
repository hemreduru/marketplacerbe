<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Models\UserMarketplaceCredential;
use App\Services\Finance\ProfitAggregator;

/**
 * Kalem bazlı kâr defterinin okuma katmanı — SUM'lar doğru, yeniden hesap yok.
 */
function seedFinancialRows(UserMarketplaceCredential $credential, int $masterId, int $count = 2): void
{
    $order = Order::factory()->create([
        'user_id' => $credential->user_id,
        'marketplace_id' => $credential->marketplace_id,
        'user_marketplace_credential_id' => $credential->id,
    ]);

    for ($i = 0; $i < $count; $i++) {
        $item = OrderItem::factory()->create(['order_id' => $order->id]);
        OrderItemFinancial::factory()->create([
            'order_item_id' => $item->id,
            'order_id' => $order->id,
            'user_marketplace_credential_id' => $credential->id,
            'master_product_id' => $masterId,
            'order_date' => now()->toDateString(),
            'net_revenue' => '100.0000',
            'cogs' => '40.0000',
            'commission' => '15.0000',
            'net_profit' => '10.0000',
        ]);
    }
}

test('forCredential dönem içi para kolonlarını toplar', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedFinancialRows($credential, $master->id, 2);

    $agg = app(ProfitAggregator::class)->forCredential(
        $credential->id,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($agg['item_count'])->toBe('2')
        ->and($agg['net_revenue'])->toBe('200.0000')
        ->and($agg['cogs'])->toBe('80.0000')
        ->and($agg['net_profit'])->toBe('20.0000')
        ->and($agg['margin'])->toBe('10.00');
});

test('forUser kullanıcının tüm mağazalarını toplar', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedFinancialRows($credential, $master->id, 3);

    $agg = app(ProfitAggregator::class)->forUser(
        $user,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($agg['item_count'])->toBe('3')
        ->and($agg['net_profit'])->toBe('30.0000');
});

test('forSku sadece ilgili master ürünü toplar', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    $other = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedFinancialRows($credential, $master->id, 2);
    seedFinancialRows($credential, $other->id, 5);

    $agg = app(ProfitAggregator::class)->forSku(
        $master->id,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($agg['item_count'])->toBe('2');
});

test('skuTable master başına P&L satırı döner', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedFinancialRows($credential, $master->id, 2);

    $rows = app(ProfitAggregator::class)->skuTable(
        $user,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($rows)->toHaveCount(1);
    expect($rows->first()['items'])->toBe(2)
        ->and($rows->first()['master_product_id'])->toBe($master->id)
        ->and($rows->first()['net_profit'])->toBe('20.0000');
});
