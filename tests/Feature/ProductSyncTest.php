<?php

use App\Models\MarketplaceSyncLog;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

test('product sync pulls products into the database and records a sync log', function () {
    [$user, $credential] = userWithTrendyol();

    Http::fake([
        '*/integration/product/sellers/*/products*' => Http::sequence()
            ->push(['content' => [[
                'productMainId' => 'PM-1',
                'barcode' => '8680000000001',
                'stockCode' => 'SKU-1',
                'title' => 'Test Product',
                'salePrice' => 99.90,
                'listPrice' => 129.90,
                'quantity' => 12,
                'approved' => true,
            ]], 'totalElements' => 1])
            ->push(['content' => [], 'totalElements' => 1]),
    ]);

    $this->actingAs($user)
        ->post(route('products.sync'))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Product::where('user_marketplace_credential_id', $credential->id)->count())->toBe(1);

    $product = Product::first();
    expect($product->title)->toBe('Test Product');
    expect((float) $product->price)->toBe(99.90);

    $log = MarketplaceSyncLog::where('user_marketplace_credential_id', $credential->id)
        ->where('entity_type', 'product')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->status)->toBe('success');
    expect($log->created_count)->toBe(1);

    expect($credential->fresh()->last_sync_at)->not->toBeNull();
});
