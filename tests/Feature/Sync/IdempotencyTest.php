<?php

use App\Models\MarketplaceSyncLog;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

test('aynı Trendyol ürünü 2 kez sync edilince tek kayıt + sayım 1', function () {
    [$user, $credential] = userWithTrendyol();

    Http::fake([
        '*/integration/product/sellers/*/products*' => Http::sequence()
            ->push(['content' => [[
                'productMainId' => 'PM-IDEMP-1',
                'barcode' => '8680000099991',
                'stockCode' => 'SKU-IDEMP-1',
                'title' => 'Idempotent Product',
                'salePrice' => 50.00,
                'listPrice' => 75.00,
                'quantity' => 10,
                'approved' => true,
            ]], 'totalElements' => 1])
            ->push(['content' => [], 'totalElements' => 1])
            // İkinci sync için aynı response
            ->push(['content' => [[
                'productMainId' => 'PM-IDEMP-1',
                'barcode' => '8680000099991',
                'stockCode' => 'SKU-IDEMP-1',
                'title' => 'Idempotent Product (renamed)',
                'salePrice' => 55.00,
                'listPrice' => 80.00,
                'quantity' => 8,
                'approved' => true,
            ]], 'totalElements' => 1])
            ->push(['content' => [], 'totalElements' => 1]),
    ]);

    // İlk sync — create
    $this->actingAs($user)->post(route('products.sync'))->assertOk();
    expect(Product::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1);

    // İkinci sync — update (yeni record DEĞİL)
    $this->actingAs($user)->post(route('products.sync'))->assertOk();
    expect(Product::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1);

    // Güncel verinin yansıdığını doğrula
    $product = Product::first();
    expect($product->title)->toBe('Idempotent Product (renamed)')
        ->and((float) $product->price)->toBe(55.00)
        ->and($product->stock)->toBe(8);

    // 2 sync = 2 log
    $logCount = MarketplaceSyncLog::where('user_marketplace_credential_id', $credential->id)
        ->where('entity_type', 'product')
        ->count();
    expect($logCount)->toBe(2);
});
