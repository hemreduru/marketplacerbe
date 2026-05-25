<?php

use App\Models\Product;
use Illuminate\Support\Facades\Http;

test('price and stock update is simulated when writes are disabled', function () {
    config(['marketplace.write_enabled' => false]);
    Http::preventStrayRequests();

    [$user, $credential] = userWithTrendyol();
    $product = Product::factory()->create(['user_marketplace_credential_id' => $credential->id]);

    $this->actingAs($user)
        ->post(route('products.update-price-stock'), [
            'product_id' => $product->id,
            'sale_price' => 149.90,
            'stock' => 25,
        ])
        ->assertOk()
        ->assertJson(['success' => true, 'message' => __('common.action_simulated')]);

    Http::assertNothingSent();
});

test('price and stock update reaches the marketplace when writes are enabled', function () {
    config(['marketplace.write_enabled' => true]);

    Http::fake([
        '*/integration/inventory/sellers/*/products/price-and-inventory' => Http::response(['batchRequestId' => 'batch-1'], 200),
    ]);

    [$user, $credential] = userWithTrendyol();
    $product = Product::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'barcode' => '8680000000123',
    ]);

    $this->actingAs($user)
        ->post(route('products.update-price-stock'), [
            'product_id' => $product->id,
            'sale_price' => 149.90,
            'stock' => 25,
        ])
        ->assertOk()
        ->assertJson(['success' => true]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/price-and-inventory')
        && $request['items'][0]['barcode'] === '8680000000123'
        && $request['items'][0]['quantity'] === 25);
});
