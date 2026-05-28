<?php

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Calculations\AdAllocator;
use App\Services\Calculations\CommissionCalculator;
use App\Services\Calculations\PackagingCostCalculator;
use App\Services\Calculations\ProfitCalculator;
use App\Services\Calculations\ReturnCostEstimator;
use App\Services\Calculations\ServiceFeeCalculator;
use App\Services\Calculations\ShippingCostCalculator;
use App\Services\Calculations\VatCalculator;

function makeCalculator(): ProfitCalculator
{
    $vat = new VatCalculator;

    return new ProfitCalculator(
        vat: $vat,
        commission: new CommissionCalculator($vat),
        serviceFee: new ServiceFeeCalculator($vat),
        shipping: new ShippingCostCalculator($vat),
        returnCost: new ReturnCostEstimator,
        packaging: new PackagingCostCalculator($vat),
        ads: new AdAllocator,
    );
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
        'cost_of_goods', 'commission', 'shipping', 'return_cost', 'ad_cost', 'packaging',
    ]);
    expect($result->deductions)->not->toHaveKey('service_fee');
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
