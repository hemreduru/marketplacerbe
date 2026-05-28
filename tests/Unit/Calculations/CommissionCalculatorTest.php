<?php

use App\Services\Calculations\CommissionCalculator;
use App\Services\Calculations\VatCalculator;

test('CommissionCalculator amount hesaplar — vat_excluded base', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);

    $result = $commission->amount(100.0, 20.0, 15.0, 'vat_excluded');

    expect($result)->toBe('12.5000');
});

test('CommissionCalculator amount hesaplar — vat_included base', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);

    $result = $commission->amount(100.0, 20.0, 15.0, 'vat_included');

    expect($result)->toBe('15.0000');
});

test('commissionVat komisyon KDV\'sini hesaplar', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);

    $result = $commission->commissionVat('12.5000', 20.0);

    expect($result)->toBe('2.5000');
});

test('totalDeduction komisyon + KDV toplamını döndürür', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);

    $result = $commission->totalDeduction(100.0, 20.0, 15.0, 'vat_excluded', 20.0);

    expect($result)->toBe('15.0000');
});

test('different marketplace commission rates', function (float $saleIncVat, float $vatRate, float $commissionRate, string $baseType, string $expected) {
    $vat = new VatCalculator;
    $calculator = new CommissionCalculator($vat);

    expect($calculator->amount($saleIncVat, $vatRate, $commissionRate, $baseType))->toBe($expected);
})->with([
    'Trendyol: 100 TL, %15, vat_excluded' => [100.0, 20.0, 15.0, 'vat_excluded', '12.5000'],
    'Hepsiburada: 100 TL, %15, vat_included' => [100.0, 20.0, 15.0, 'vat_included', '15.0000'],
    'High commission: 500 TL, %20, vat_excluded' => [500.0, 20.0, 20.0, 'vat_excluded', '83.3333'],
    'Zero rate: 100 TL, %0' => [100.0, 20.0, 0.0, 'vat_excluded', '0.0000'],
]);

test('base method returns correct base amount', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);

    expect($commission->base(100.0, 20.0, 'vat_excluded'))->toBe('83.3333');
    expect($commission->base(100.0, 20.0, 'vat_included'))->toBe('100.0000');
});
