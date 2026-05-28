<?php

use App\Services\Calculations\ShippingCostCalculator;
use App\Services\Calculations\VatCalculator;

test('base desi içinde kalan kargo hesaplanır', function () {
    $vat = new VatCalculator;
    $calculator = new ShippingCostCalculator($vat);

    $tariff = ['base_desi' => 5, 'base_price' => '40.00', 'per_extra_desi_price' => '5.00'];

    $result = $calculator->compute(3.0, 500, $tariff);

    expect($result['excl_vat'])->toBe('40.0000');
    expect($result['vat'])->toBe('8.0000');
    expect($result['total'])->toBe('48.0000');
});

test('extra desi ücreti hesaplanır', function () {
    $vat = new VatCalculator;
    $calculator = new ShippingCostCalculator($vat);

    $tariff = ['base_desi' => 5, 'base_price' => '40.00', 'per_extra_desi_price' => '5.00'];

    $result = $calculator->compute(7.0, 500, $tariff);

    expect($result['excl_vat'])->toBe('50.0000');
});

test('weight desi\'yi override eder', function () {
    $vat = new VatCalculator;
    $calculator = new ShippingCostCalculator($vat);

    $tariff = ['base_desi' => 5, 'base_price' => '40.00', 'per_extra_desi_price' => '5.00'];

    $result = $calculator->compute(1.0, 50000, $tariff);

    $effectiveDesi = bcdiv('50000', '5000', 4);
    expect((float) $effectiveDesi)->toBe(10.0);
    expect($result['excl_vat'])->toBe('65.0000');
});
