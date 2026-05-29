<?php

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('ürün sync legacy Product yanında master_product + marketplace_listing oluşturur ve bağlar', function () {
    [$user, $credential] = userWithTrendyol();

    Http::fake([
        '*/integration/product/sellers/*/products*' => Http::sequence()
            ->push(['content' => [[
                'productMainId' => 'PM-BRIDGE-1',
                'barcode' => '8680000011111',
                'stockCode' => 'SKU-BRIDGE-1',
                'title' => 'Köprü Ürünü',
                'salePrice' => 120.00,
                'quantity' => 7,
                'approved' => true,
                'productUrl' => 'https://trendyol.com/p/1',
            ]], 'totalElements' => 1])
            ->push(['content' => [], 'totalElements' => 1]),
    ]);

    $this->actingAs($user)->post(route('products.sync'))->assertOk();

    $master = MasterProduct::where('user_id', $user->id)->first();
    expect($master)->not->toBeNull()
        ->and($master->barcode)->toBe('8680000011111');

    $listing = MarketplaceListing::where('user_marketplace_credential_id', $credential->id)->first();
    expect($listing)->not->toBeNull()
        ->and($listing->remote_product_id)->toBe('PM-BRIDGE-1')
        ->and($listing->remote_sku)->toBe('SKU-BRIDGE-1')
        ->and($listing->remote_barcode)->toBe('8680000011111')
        ->and($listing->master_product_id)->toBe($master->id);
});

test('aynı ürün 2 kez sync edilince tek master + tek listing kalır', function () {
    [$user, $credential] = userWithTrendyol();

    $item = [[
        'productMainId' => 'PM-BRIDGE-2',
        'barcode' => '8680000022222',
        'stockCode' => 'SKU-BRIDGE-2',
        'title' => 'Tekil Köprü',
        'salePrice' => 90.00,
        'quantity' => 3,
        'approved' => true,
    ]];

    Http::fake([
        '*/integration/product/sellers/*/products*' => Http::sequence()
            ->push(['content' => $item, 'totalElements' => 1])
            ->push(['content' => [], 'totalElements' => 1])
            ->push(['content' => $item, 'totalElements' => 1])
            ->push(['content' => [], 'totalElements' => 1]),
    ]);

    $this->actingAs($user)->post(route('products.sync'))->assertOk();
    $this->actingAs($user)->post(route('products.sync'))->assertOk();

    expect(MasterProduct::where('user_id', $user->id)->count())->toBe(1)
        ->and(MarketplaceListing::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1);
});

test('aynı barcode iki listing tek master altında toplanır', function () {
    [$user, $credential] = userWithTrendyol();

    Http::fake([
        '*/integration/product/sellers/*/products*' => Http::sequence()
            ->push(['content' => [
                [
                    'productMainId' => 'PM-A',
                    'barcode' => '8680000033333',
                    'stockCode' => 'SKU-A',
                    'title' => 'Varyant A',
                    'salePrice' => 50.00,
                    'quantity' => 5,
                    'approved' => true,
                ],
                [
                    'productMainId' => 'PM-B',
                    'barcode' => '8680000033333',
                    'stockCode' => 'SKU-B',
                    'title' => 'Varyant B',
                    'salePrice' => 55.00,
                    'quantity' => 2,
                    'approved' => true,
                ],
            ], 'totalElements' => 2])
            ->push(['content' => [], 'totalElements' => 2]),
    ]);

    $this->actingAs($user)->post(route('products.sync'))->assertOk();

    expect(MasterProduct::where('user_id', $user->id)->count())->toBe(1)
        ->and(MarketplaceListing::where('user_marketplace_credential_id', $credential->id)->count())->toBe(2);

    $masterId = MasterProduct::where('user_id', $user->id)->value('id');
    expect(MarketplaceListing::where('master_product_id', $masterId)->count())->toBe(2);
});
