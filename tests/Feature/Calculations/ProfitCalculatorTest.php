<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Calculations\ProfitCalculator;
use App\Services\Finance\ProfitContextFactory;

function makeCalculator(): ProfitCalculator
{
    return app(ProfitCalculator::class);
}

test('tek kalem net kâr — 200 TL satış, düşük maliyet', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 30.0,
        'packaging_cost' => 1.0,
        'weight_g' => 500,
        'desi' => 1.0,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 200.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
    ]);

    $calculator = makeCalculator();
    $result = $calculator->forOrderItem($item, $master);

    expect($result->netRevenue)->toBe('166.6667');
    expect($result->netProfit)->toBeString();
    expect((float) $result->netProfit)->toBeGreaterThan(0);
    expect((float) $result->margin)->toBeGreaterThan(0);
    expect($result->deductions)->toHaveKeys([
        'cost_of_goods', 'commission', 'shipping', 'stopaj', 'return_cost', 'ad_cost', 'packaging',
    ]);
    expect($result->deductions)->not->toHaveKey('service_fee');
});

test('stopaj KDV hariç matrah üzerinden yüzde 1 düşülür', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 30.0,
        'vat_rate' => 20.00,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 200.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
    ]);

    $result = makeCalculator()->forOrderItem($item, $master);

    // 200 / 1.2 = 166.6667 matrah → %1 = 1.6667
    expect($result->deductions['stopaj'])->toBe('1.6667');
});

test('ürünün gerçek KDV oranı kullanılır — %10 KDV', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 30.0,
        'vat_rate' => 10.00,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 220.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
    ]);

    $result = makeCalculator()->forOrderItem($item, $master);

    // 220 / 1.10 = 200.0000 (hardcoded %20 olsaydı 183.3333 çıkardı)
    expect($result->netRevenue)->toBe('200.0000');
});

test('hepsiburada bağlamında komisyon KDV dahil bazdan hesaplanır', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 30.0,
        'vat_rate' => 20.00,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 200.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
        'commission_rate' => 0,
    ]);

    $calculator = makeCalculator();
    $factory = app(ProfitContextFactory::class);

    $trendyol = $calculator->forOrderItem($item, $master, $factory->forMarketplace('trendyol'));
    $hepsiburada = $calculator->forOrderItem($item, $master, $factory->forMarketplace('hepsiburada'));

    // TY: KDV hariç 166.6667 × %15 = 25.0000 | HB: KDV dahil 200 × %15 = 30.0000
    expect($trendyol->deductions['commission'])->toBe('25.0000')
        ->and($hepsiburada->deductions['commission'])->toBe('30.0000')
        ->and($hepsiburada->details['marketplace'])->toBe('hepsiburada');
});

test('kalemde settlement komisyon oranı varsa config yerine o kullanılır', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 30.0,
        'vat_rate' => 20.00,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 200.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
        'commission_rate' => 21.50,
    ]);

    $result = makeCalculator()->forOrderItem($item, $master);

    // 166.6667 × %21.5 = 35.8333
    expect($result->deductions['commission'])->toBe('35.8333')
        ->and((float) $result->details['commission_rate'])->toBe(21.5);
});

test('multi-item order — platform fee sipariş başına 1 kez', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 30.0,
        'packaging_cost' => 1.0,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);

    for ($i = 0; $i < 5; $i++) {
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'price' => 50.00,
            'quantity' => 1,
            'master_product_id' => $master->id,
        ]);
    }

    $calculator = makeCalculator();
    $result = $calculator->forOrder($order);
    $itemsBreakdown = [];

    foreach ($order->items as $item) {
        $itemsBreakdown[] = $calculator->forOrderItem($item, $master);
    }

    $itemsTotalProfit = '0.0000';
    foreach ($itemsBreakdown as $b) {
        $itemsTotalProfit = bcadd($itemsTotalProfit, $b->netProfit, 6);
    }

    $serviceFeeExcl = '8.4900';
    $orderProfit = bcsub($itemsTotalProfit, $serviceFeeExcl, 6);

    expect($result->netProfit)->toBe(bcround($orderProfit, 4));
    expect((int) $result->details['item_count'])->toBe(5);
    expect($result->deductions['service_fee'])->toBe('8.4900');
});

test('net kâr sıfır ve altı edge case', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 200.0,
        'packaging_cost' => 10.0,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 100.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
    ]);

    $calculator = makeCalculator();
    $result = $calculator->forOrderItem($item, $master);

    expect((float) $result->netProfit)->toBeLessThan(0);
    expect((float) $result->margin)->toBeLessThan(0);
});

test('ProfitBreakdown toArray döndürür', function () {
    $user = User::factory()->create();
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'cost_price' => 50.0,
    ]);
    $order = Order::factory()->create(['user_id' => $user->id]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'price' => 100.00,
        'quantity' => 1,
        'master_product_id' => $master->id,
    ]);

    $calculator = makeCalculator();
    $result = $calculator->forOrderItem($item, $master);
    $array = $result->toArray();

    expect($array)->toHaveKeys(['net_revenue', 'net_profit', 'margin', 'roi', 'deductions', 'details']);
});
