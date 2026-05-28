<?php

use Illuminate\Support\Facades\Blade;

test('@money pozitif değeri Türkçe locale ile formatlar', function () {
    expect(Blade::render('@money($v)', ['v' => 1234.56]))->toBe('1.234,56 ₺');
});

test('@money tam sayıyı iki ondalık ile gösterir', function () {
    expect(Blade::render('@money($v)', ['v' => 100]))->toBe('100,00 ₺');
});

test('@money sıfır değerini gösterir', function () {
    expect(Blade::render('@money($v)', ['v' => 0]))->toBe('0,00 ₺');
});

test('@money negatif değeri gösterir', function () {
    expect(Blade::render('@money($v)', ['v' => -45.5]))->toBe('-45,50 ₺');
});

test('@money milyonluk değer için binlik ayırıcı kullanır', function () {
    expect(Blade::render('@money($v)', ['v' => 1234567.89]))->toBe('1.234.567,89 ₺');
});

test('@money string sayısal değeri kabul eder', function () {
    expect(Blade::render('@money($v)', ['v' => '99.9']))->toBe('99,90 ₺');
});
