<?php

use App\Services\Calculations\VatCalculator;

test('excludeVat KDV dahil fiyattan KDV hariç fiyatı hesaplar', function () {
    $calculator = new VatCalculator;

    $result = $calculator->excludeVat(100.0, 20.0);

    expect($result)->toBe('83.3333');
});

test('excludeVat farklı KDV oranlarıyla doğru çalışır', function (float $incVat, float $rate, string $expected) {
    $calculator = new VatCalculator;

    expect($calculator->excludeVat($incVat, $rate))->toBe($expected);
})->with([
    '100 TL %20 KDV' => [100.0, 20.0, '83.3333'],
    '120 TL %20 KDV' => [120.0, 20.0, '100.0000'],
    '118 TL %18 KDV' => [118.0, 18.0, '100.0000'],
    '101 TL %1 KDV' => [101.0, 1.0, '100.0000'],
    '9999.99 TL %20 KDV' => [9999.99, 20.0, '8333.3250'],
]);

test('excludeVat sıfır KDV oranında fiyatı aynen döndürür', function () {
    $calculator = new VatCalculator;

    expect($calculator->excludeVat(100.0, 0.0))->toBe('100.0000');
});

test('vatAmount KDV tutarını hesaplar', function () {
    $calculator = new VatCalculator;

    $result = $calculator->vatAmount(100.0, 20.0);

    expect($result)->toBe('16.6667');
});

test('vatAmount farklı KDV oranlarıyla doğru çalışır', function (float $incVat, float $rate, string $expected) {
    $calculator = new VatCalculator;

    expect($calculator->vatAmount($incVat, $rate))->toBe($expected);
})->with([
    '100 TL %20 KDV → 16.6667' => [100.0, 20.0, '16.6667'],
    '120 TL %20 KDV → 20.0000' => [120.0, 20.0, '20.0000'],
    '118 TL %18 KDV → 18.0000' => [118.0, 18.0, '18.0000'],
    '101 TL %1 KDV → 1.0000' => [101.0, 1.0, '1.0000'],
]);

test('vatAmount sıfır KDV oranında sıfır döndürür', function () {
    $calculator = new VatCalculator;

    expect($calculator->vatAmount(100.0, 0.0))->toBe('0.0000');
});

test('includeVat KDV hariç fiyata KDV ekler', function () {
    $calculator = new VatCalculator;

    $result = $calculator->includeVat(100.0, 20.0);

    expect($result)->toBe('120.0000');
});

test('includeVat farklı KDV oranlarıyla doğru çalışır', function (float $exVat, float $rate, string $expected) {
    $calculator = new VatCalculator;

    expect($calculator->includeVat($exVat, $rate))->toBe($expected);
})->with([
    '100 TL %20 KDV → 120.0000' => [100.0, 20.0, '120.0000'],
    '83.3333 TL %20 KDV → 100.0000' => [83.3333, 20.0, '100.0000'],
    '50 TL %18 KDV → 59.0000' => [50.0, 18.0, '59.0000'],
    '200 TL %1 KDV → 202.0000' => [200.0, 1.0, '202.0000'],
    '0 TL %20 KDV → 0.0000' => [0.0, 20.0, '0.0000'],
]);

test('excludeVat + vatAmount toplamı orijinal fiyata eşittir', function (float $incVat, float $rate) {
    $calculator = new VatCalculator;

    $exVat = $calculator->excludeVat($incVat, $rate);
    $vat = $calculator->vatAmount($incVat, $rate);

    $sum = bcadd($exVat, $vat, 4);

    $tolerance = bccomp(abs($incVat - (float) $sum), '0.001', 3) <= 0
        || bccomp($sum, (string) round($incVat, 4), 4) === 0;

    expect($tolerance)->toBeTrue();
})->with([
    [100.0, 20.0],
    [118.0, 18.0],
    [250.50, 20.0],
    [1000.0, 1.0],
]);

test('includeVat ters işlemi excludeVat ile tutarlıdır', function () {
    $calculator = new VatCalculator;

    $exVat = '83.3333';
    $incVat = $calculator->includeVat((float) $exVat, 20.0);
    $back = $calculator->excludeVat((float) $incVat, 20.0);

    expect($back)->toBe('83.3333');
});
