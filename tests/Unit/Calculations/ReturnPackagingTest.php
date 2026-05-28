<?php

use App\Services\Calculations\PackagingCostCalculator;
use App\Services\Calculations\ReturnCostEstimator;
use App\Services\Calculations\VatCalculator;

test('ReturnCostEstimator dönüş maliyetini hesaplar', function () {
    $estimator = new ReturnCostEstimator;

    $result = $estimator->expectedReturnCost(0.10, 50.0);

    expect($result)->toBe('10.0000');
});

test('ReturnCostEstimator sıfır return rate sıfır maliyet', function () {
    $estimator = new ReturnCostEstimator;

    expect($estimator->expectedReturnCost(0.0, 50.0))->toBe('0.0000');
});

test('PackagingCostCalculator KDV hariç paketleme maliyeti', function () {
    $vat = new VatCalculator;
    $calculator = new PackagingCostCalculator($vat);

    $result = $calculator->calculateFromCost('5.00', 20.0);

    expect($result['excl_vat'])->toBe('4.1667');
    expect($result['vat'])->toBe('0.8333');
    expect($result['total'])->toBe('5.0000');
});

test('PackagingCostCalculator sıfır maliyet', function () {
    $vat = new VatCalculator;
    $calculator = new PackagingCostCalculator($vat);

    $result = $calculator->calculateFromCost('0.00', 20.0);

    expect($result['excl_vat'])->toBe('0.0000');
});
