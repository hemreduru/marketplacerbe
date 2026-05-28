<?php

use App\Services\Calculations\CommissionCalculator;
use App\Services\Calculations\NetVatLiability;
use App\Services\Calculations\VatCalculator;

test('net KDV yükümlülüğü pozitif olabilir', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);
    $calculator = new NetVatLiability($vat, $commission);

    $result = $calculator->calculate(
        saleIncVat: 120.0,       // 100 + 20 KDV
        saleVatRate: 20.0,
        costIncVat: 60.0,         // 50 + 10 KDV
        costVatRate: 20.0,
        commissionAmount: 12.5,   // komisyon
        commissionVatRate: 20.0,
        shippingIncVat: 20.0,     // kargo
        shippingVatRate: 20.0,
        platformFeeExclVat: 8.49, // platform bedeli KDV hariç
        platformFeeVatRate: 20.0,
    );

    expect($result['sale_vat'])->toBe('20.0000');
    expect($result['purchase_vat_refund'])->toBe('10.0000');
    expect($result['commission_vat_refund'])->toBe('2.5000');
    expect((float) $result['net_liability'])->toBeGreaterThan(0);
});

test('net KDV yükümlülüğü negatif olabilir — alacaklı pozisyon', function () {
    $vat = new VatCalculator;
    $commission = new CommissionCalculator($vat);
    $calculator = new NetVatLiability($vat, $commission);

    $result = $calculator->calculate(
        saleIncVat: 120.0,
        saleVatRate: 20.0,
        costIncVat: 600.0,
        costVatRate: 20.0,
        commissionAmount: 12.5,
        commissionVatRate: 20.0,
        shippingIncVat: 100.0,
        shippingVatRate: 20.0,
        platformFeeExclVat: 8.49,
        platformFeeVatRate: 20.0,
    );

    expect((float) $result['purchase_vat_refund'])->toBe(100.0);
    expect((float) $result['net_liability'])->toBeLessThan(0);
});
