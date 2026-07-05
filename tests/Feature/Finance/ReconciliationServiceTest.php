<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Models\UserMarketplaceCredential;
use App\Services\Finance\ReconciliationService;

/**
 * Tahmin ↔ settlement gerçeği mutabakatı ve "para iade" anomali tespiti.
 */
function seedSettledItem(UserMarketplaceCredential $credential, int $masterId, array $overrides = []): OrderItemFinancial
{
    $order = Order::factory()->create([
        'user_id' => $credential->user_id,
        'marketplace_id' => $credential->marketplace_id,
        'user_marketplace_credential_id' => $credential->id,
    ]);
    $item = OrderItem::factory()->create(['order_id' => $order->id]);

    return OrderItemFinancial::factory()->settled()->create(array_merge([
        'order_item_id' => $item->id,
        'order_id' => $order->id,
        'user_marketplace_credential_id' => $credential->id,
        'master_product_id' => $masterId,
        'order_date' => now()->toDateString(),
        'estimated_net_profit' => '100.0000',
        'net_profit' => '90.0000',
    ], $overrides));
}

test('portfolioDeviation tahmin ve gerçek sapmayı hesaplar', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedSettledItem($credential, $master->id);

    $result = app(ReconciliationService::class)->portfolioDeviation(
        $user,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($result['estimated'])->toBe('100.0000')
        ->and($result['actual'])->toBe('90.0000')
        ->and($result['deviation_pct'])->toBe('-10.00')
        ->and($result['settled_items'])->toBe(1);
});

test('bySku settlement görmüş kalemleri SKU bazında gruplar', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedSettledItem($credential, $master->id);
    seedSettledItem($credential, $master->id, ['estimated_net_profit' => '50.0000', 'net_profit' => '50.0000']);

    $rows = app(ReconciliationService::class)->bySku(
        $user,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($rows)->toHaveCount(1);
    expect($rows->first()['items'])->toBe(2)
        ->and($rows->first()['estimated_net_profit'])->toBe('150.0000')
        ->and($rows->first()['actual_net_profit'])->toBe('140.0000');
});

test('anomalies fazla kesilen komisyonu kanıtla işaretler', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedSettledItem($credential, $master->id, [
        'commission' => '20.0000',
        'shipping' => '0.0000',
        'estimate_breakdown' => ['deductions' => ['commission' => '10.0000', 'shipping' => '0']],
    ]);

    $anomalies = app(ReconciliationService::class)->anomalies(
        $user,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($anomalies)->toHaveCount(1);
    expect($anomalies->first()['type'])->toBe('commission_overcharge')
        ->and($anomalies->first()['evidence'])->toHaveKey('order_item_financial_id');
});

test('reklam sapması anomali sayılmaz', function () {
    [$user, $credential] = userWithTrendyol();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    seedSettledItem($credential, $master->id, [
        'ad_cost' => '50.0000',
        'commission' => '15.0000',
        'shipping' => '40.0000',
        'estimate_breakdown' => ['deductions' => ['commission' => '15.0000', 'shipping' => '40.0000']],
    ]);

    $anomalies = app(ReconciliationService::class)->anomalies(
        $user,
        now()->startOfMonth()->toDateString(),
        now()->toDateString()
    );

    expect($anomalies)->toBeEmpty();
});
