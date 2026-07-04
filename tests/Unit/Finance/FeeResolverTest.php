<?php

use App\Services\Finance\FeeResolver;

test('trendyol profili KDV hariç komisyon bazı ile döner', function () {
    $profile = (new FeeResolver)->profileFor('trendyol');

    expect($profile->code)->toBe('trendyol')
        ->and($profile->commissionBaseType)->toBe('vat_excluded')
        ->and($profile->commissionDefaultRate)->toBe('15')
        ->and($profile->commissionVatRate)->toBe('20')
        ->and($profile->stopajRate)->toBe('1')
        ->and($profile->shippingTariff)->toHaveKeys(['base_desi', 'base_price', 'per_extra_desi_price']);
});

test('hepsiburada profili KDV dahil komisyon bazı ve 8 TL hizmet bedeli ile döner', function () {
    $profile = (new FeeResolver)->profileFor('hepsiburada');

    expect($profile->commissionBaseType)->toBe('vat_included')
        ->and($profile->stopajRate)->toBe('1');

    $fee = $profile->serviceFee('standard');
    expect($fee['amount_excl_vat'])->toBe('8')
        ->and($fee['vat_rate'])->toBe('20');
});

test('trendyol standard hizmet bedeli 8.49 TL', function () {
    $profile = (new FeeResolver)->profileFor('trendyol');

    expect($profile->serviceFee('standard')['amount_excl_vat'])->toBe('8.49');
});

test('bilinmeyen sipariş tipi standard hizmet bedeline düşer', function () {
    $profile = (new FeeResolver)->profileFor('trendyol');

    expect($profile->serviceFee('nonexistent_type'))->toBe($profile->serviceFee('standard'));
});

test('config eksik pazaryeri için güvenli varsayılanlar döner', function () {
    config()->set('marketplaces.nonexistent', ['code' => 'nonexistent']);

    $profile = (new FeeResolver)->profileFor('nonexistent');

    expect($profile->commissionBaseType)->toBe('vat_excluded')
        ->and($profile->commissionDefaultRate)->toBe('15')
        ->and($profile->stopajRate)->toBe('1')
        ->and($profile->saleVatRate)->toBe('20');
});

test('satış KDV varsayılanı config vat_rates.sale_default kaynaklıdır', function () {
    $profile = (new FeeResolver)->profileFor('trendyol');

    expect($profile->saleVatRate)->toBe('20')
        ->and($profile->shippingVatRate)->toBe('20');
});
