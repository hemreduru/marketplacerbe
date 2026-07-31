<?php

use App\Services\Marketplaces\MarketplaceCapability;

it('Trendyol buybox destekler, HB desteklemez', function () {
    expect(MarketplaceCapability::supports('trendyol', 'buybox'))->toBeTrue();
    expect(MarketplaceCapability::supports('hepsiburada', 'buybox'))->toBeFalse();
});

it('limit() yapısal alanları okur', function () {
    expect(MarketplaceCapability::limit('trendyol', 'product_title_max'))->toBe(100);
    expect(MarketplaceCapability::limit('hepsiburada', 'product_description_max'))->toBe(5000);
    expect(MarketplaceCapability::limit('n11', 'polling_interval_seconds'))->toBe(300);
});

it('rateLimit() bucket çözümler', function () {
    expect(MarketplaceCapability::rateLimit('trendyol'))->toBe(600);
    expect(MarketplaceCapability::rateLimit('trendyol', 'buybox'))->toBe(1000);
    expect(MarketplaceCapability::rateLimit('pazarama'))->toBe(60);
});

it('all() tüm pazaryeri code listesini döner', function () {
    expect(MarketplaceCapability::all())->toContain('trendyol', 'hepsiburada', 'n11', 'pazarama');
});

it('manifest() tüm konfigi geri verir', function () {
    $trendyol = MarketplaceCapability::manifest('trendyol');

    expect($trendyol)->toBeArray();
    expect($trendyol['code'])->toBe('trendyol');
    expect($trendyol['name'])->toBe('Trendyol');
});

it('N11 webhook olmadığı için orders_webhook desteklemez', function () {
    expect(MarketplaceCapability::supports('n11', 'orders_webhook'))->toBeFalse();
});
