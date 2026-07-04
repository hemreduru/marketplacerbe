<?php

use App\Support\MoneyAllocator;

test('eşit ağırlıklarla dağıtım toplamı tam tutara eşittir', function () {
    $shares = MoneyAllocator::distribute('100.0000', ['1', '1', '1']);

    expect($shares)->toHaveCount(3);

    $sum = array_reduce($shares, fn (string $carry, string $s) => bcadd($carry, $s, 4), '0.0000');
    expect($sum)->toBe('100.0000');
});

test('artık kuruş en büyük ağırlıklı kaleme eklenir', function () {
    // 100 / 3 = 33.3333 → toplam 99.9999; artık 0.0001 en büyük paylı (ilk) kaleme
    $shares = MoneyAllocator::distribute('100.0000', ['1', '1', '1']);

    expect($shares)->toBe(['33.3334', '33.3333', '33.3333']);
});

test('orantılı dağıtım ağırlıklara göre yapılır', function () {
    $shares = MoneyAllocator::distribute('100.0000', ['3', '1']);

    expect($shares)->toBe(['75.0000', '25.0000']);
});

test('küçük tutarlar kayıpsız dağıtılır', function () {
    $shares = MoneyAllocator::distribute('0.0100', ['1', '1', '1']);

    $sum = array_reduce($shares, fn (string $carry, string $s) => bcadd($carry, $s, 4), '0.0000');
    expect($sum)->toBe('0.0100');
});

test('tüm ağırlıklar sıfırsa eşit dağıtılır', function () {
    $shares = MoneyAllocator::distribute('90.0000', ['0', '0', '0']);

    $sum = array_reduce($shares, fn (string $carry, string $s) => bcadd($carry, $s, 4), '0.0000');
    expect($sum)->toBe('90.0000')
        ->and($shares[0])->toBe('30.0000');
});

test('boş ağırlık listesi boş dizi döner', function () {
    expect(MoneyAllocator::distribute('100.0000', []))->toBe([]);
});

test('tek kalem tüm tutarı alır', function () {
    expect(MoneyAllocator::distribute('42.5500', ['7']))->toBe(['42.5500']);
});

test('negatif toplam (iade) da tam dağıtılır', function () {
    $shares = MoneyAllocator::distribute('-10.0000', ['1', '1', '1']);

    $sum = array_reduce($shares, fn (string $carry, string $s) => bcadd($carry, $s, 4), '0.0000');
    expect($sum)->toBe('-10.0000');
});
