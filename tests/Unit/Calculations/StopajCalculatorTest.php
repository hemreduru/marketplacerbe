<?php

use App\Services\Calculations\StopajCalculator;
use App\Services\Calculations\VatCalculator;

function makeStopajCalculator(): StopajCalculator
{
    return new StopajCalculator(new VatCalculator);
}

test('stopaj KDV hariç matrah üzerinden yüzde 1 hesaplanır', function () {
    $calculator = makeStopajCalculator();

    // 120 TL KDV dahil, %20 KDV → matrah 100 TL → %1 stopaj = 1 TL
    expect($calculator->amount(120.0, 20.0))->toBe('1.0000');
});

test('stopaj farklı KDV oranlarıyla doğru matrahtan hesaplanır', function (float|string $incVat, float $vatRate, string $expected) {
    $calculator = makeStopajCalculator();

    expect($calculator->amount($incVat, $vatRate))->toBe($expected);
})->with([
    '%20 KDV — 120 TL → matrah 100' => [120.0, 20.0, '1.0000'],
    '%10 KDV — 110 TL → matrah 100' => [110.0, 10.0, '1.0000'],
    '%1 KDV — 101 TL → matrah 100' => [101.0, 1.0, '1.0000'],
    '%0 KDV — 100 TL → matrah 100' => [100.0, 0.0, '1.0000'],
    'string girdi' => ['240.0000', 20.0, '2.0000'],
]);

test('stopaj özel oran ile hesaplanabilir', function () {
    $calculator = makeStopajCalculator();

    expect($calculator->amount(120.0, 20.0, '2.0'))->toBe('2.0000');
});

test('stopaj sıfır tutar için sıfır döner', function () {
    $calculator = makeStopajCalculator();

    expect($calculator->amount(0, 20.0))->toBe('0.0000');
});

test('stopaj oranı sıfırsa sıfır döner', function () {
    $calculator = makeStopajCalculator();

    expect($calculator->amount(120.0, 20.0, '0'))->toBe('0.0000');
});

test('stopaj sonucu bcmath string döner ve 4 hane hassasiyetlidir', function () {
    $calculator = makeStopajCalculator();

    $result = $calculator->amount(99.99, 20.0);

    // 99.99 / 1.2 = 83.3250 → %1 = 0.8333 (banker değil, bcround)
    expect($result)->toBeString()->toBe('0.8333');
});
