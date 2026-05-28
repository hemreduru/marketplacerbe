<?php

use App\Services\Calculations\ServiceFeeCalculator;
use App\Services\Calculations\VatCalculator;

test('trendyol standard platform service fee hesaplar', function () {
    $vat = new VatCalculator;
    $calculator = new ServiceFeeCalculator($vat);

    $result = $calculator->calculate('trendyol', 'standard', 1);

    expect($result['amount_excl_vat'])->toBe('8.4900');
    expect($result['vat'])->toBe('1.6980');
    expect($result['total'])->toBe('10.1880');
});

test('trendyol today_shipping indirimli fee hesaplar', function () {
    $vat = new VatCalculator;
    $calculator = new ServiceFeeCalculator($vat);

    $result = $calculator->calculate('trendyol', 'today_shipping', 1);

    expect($result['amount_excl_vat'])->toBe('5.4900');
    expect($result['total'])->toBe('6.5880');
});

test('çoklu paket fee doğru çarpılır', function () {
    $vat = new VatCalculator;
    $calculator = new ServiceFeeCalculator($vat);

    $result = $calculator->calculate('trendyol', 'standard', 3);

    expect($result['total'])->toBe('30.5640');
});

test('service fee extracting VAT returns only VAT-exclusive portion', function () {
    $vat = new VatCalculator;
    $calculator = new ServiceFeeCalculator($vat);

    $fee = $calculator->amountExcludingVat('trendyol', 'standard');

    expect($fee)->toBe('8.4900');
});
