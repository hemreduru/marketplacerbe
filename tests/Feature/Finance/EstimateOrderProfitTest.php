<?php

use App\Jobs\EstimateOrderProfitJob;
use App\Models\Marketplace;
use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderItemFinancial;
use App\Models\User;
use App\Support\Enums\ProfitSource;
use App\Support\Enums\ReconciliationStatus;
use Illuminate\Support\Facades\Queue;

function runEstimateJob(int $orderId): void
{
    app()->call([new EstimateOrderProfitJob($orderId), 'handle']);
}

test('tahmin job kalem başına doğru kâr satırı yazar', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 60.0,
        'cost_price_vat_rate' => 20.00,
        'vat_rate' => 20.00,
        'desi' => 1.0,
        'weight_g' => 500,
        'packaging_cost' => 0,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id, 'order_date' => '2026-06-15']);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 120.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
        'commission_rate' => 0,
    ]);

    runEstimateJob($order->id);

    $row = OrderItemFinancial::where('order_item_id', $item->id)->first();

    expect($row)->not->toBeNull()
        ->and($row->net_revenue)->toBe('100.0000')
        ->and($row->cogs)->toBe('50.0000')
        ->and($row->commission)->toBe('15.0000')
        ->and($row->shipping)->toBe('40.0000')
        ->and($row->stopaj)->toBe('1.0000')
        ->and($row->service_fee)->toBe('8.4900')
        // 100 − (50 + 15 + 40 + 1 + 8.49) = −14.49
        ->and($row->net_profit)->toBe('-14.4900')
        ->and($row->source)->toBe(ProfitSource::Estimate)
        ->and($row->reconciliation_status)->toBe(ReconciliationStatus::Estimated)
        ->and($row->estimated_net_profit)->toBe($row->net_profit)
        ->and($row->marketplace_code)->toBe('trendyol')
        ->and($row->order_date->toDateString())->toBe('2026-06-15');
});

test('hizmet bedeli kalemlere net gelir payıyla kayıpsız dağıtılır', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create(['user_id' => $user->id, 'vat_rate' => 20.00]);
    $order = Order::factory()->create(['user_id' => $user->id]);

    OrderItem::factory()->create([
        'order_id' => $order->id, 'price' => 120.00, 'quantity' => 1, 'master_product_id' => $master->id,
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id, 'price' => 240.00, 'quantity' => 1, 'master_product_id' => $master->id,
    ]);

    runEstimateJob($order->id);

    $totalFee = OrderItemFinancial::where('order_id', $order->id)
        ->get()
        ->reduce(fn (string $carry, $row) => bcadd($carry, $row->service_fee, 4), '0.0000');

    expect($totalFee)->toBe('8.4900');
});

test('tahmin job idempotenttir — iki koşuda tek satır', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id, 'price' => 100.00, 'quantity' => 1, 'master_product_id' => $master->id,
    ]);

    runEstimateJob($order->id);
    runEstimateJob($order->id);

    expect(OrderItemFinancial::where('order_item_id', $item->id)->count())->toBe(1);
});

test('settlement kaynaklı satır tahminle ezilmez', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id, 'price' => 100.00, 'quantity' => 1, 'master_product_id' => $master->id,
    ]);

    OrderItemFinancial::factory()->settled()->create([
        'order_item_id' => $item->id,
        'order_id' => $order->id,
        'commission' => '99.0000',
    ]);

    runEstimateJob($order->id);

    $row = OrderItemFinancial::where('order_item_id', $item->id)->first();

    expect($row->commission)->toBe('99.0000')
        ->and($row->source)->toBe(ProfitSource::Settlement);
});

test('master bağlantısı olmayan kalem barcode ile eşleştirilir (self-healing)', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'barcode' => 'BC-12345',
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 100.00,
        'quantity' => 1,
        'master_product_id' => null,
        'barcode' => 'BC-12345',
    ]);

    runEstimateJob($order->id);

    $row = OrderItemFinancial::where('order_item_id', $item->id)->first();

    expect($row->master_product_id)->toBe($master->id)
        ->and($item->fresh()->master_product_id)->toBe($master->id);
});

test('backfill komutu kâr kaydı olmayan siparişler için job dispatch eder', function () {
    Queue::fake();

    $user = User::factory()->create();
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();

    // Kaydı olmayan sipariş
    $orderWithout = Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $marketplace->id]);
    OrderItem::factory()->create([
        'order_id' => $orderWithout->id, 'price' => 100, 'quantity' => 1, 'master_product_id' => $master->id,
    ]);

    // Kaydı olan sipariş
    $orderWith = Order::factory()->create(['user_id' => $user->id, 'marketplace_id' => $marketplace->id]);
    $itemWith = OrderItem::factory()->create([
        'order_id' => $orderWith->id, 'price' => 100, 'quantity' => 1, 'master_product_id' => $master->id,
    ]);
    OrderItemFinancial::factory()->create(['order_item_id' => $itemWith->id, 'order_id' => $orderWith->id]);

    $this->artisan('profit:estimate-backfill')->assertSuccessful();

    Queue::assertPushed(EstimateOrderProfitJob::class, 1);
    Queue::assertPushed(fn (EstimateOrderProfitJob $job) => $job->orderId === $orderWithout->id);
});
