<?php

use App\Services\Marketplaces\Trendyol\Client;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::preventStrayRequests();
});

it('reads config tabanlı base URL üretir (stage)', function () {
    config()->set('marketplaces.trendyol.base_url', [
        'production' => 'https://apigw.trendyol.com',
        'stage' => 'https://stageapigw.trendyol.com',
    ]);

    $client = new Client('key', 'secret', '1000', true);

    expect($client->getBaseUrl())->toBe('https://stageapigw.trendyol.com');
});

it('reads config tabanlı base URL üretir (production)', function () {
    $client = new Client('key', 'secret', '1000', false);

    expect($client->getBaseUrl())->toBe('https://apigw.trendyol.com');
});

it('get() başarılı response ServiceResult::ok döner', function () {
    Http::fake([
        'apigw.trendyol.com/*' => Http::response(['content' => [['id' => 1]]], 200),
    ]);

    $client = new Client('key', 'secret', '1000', false);
    $result = $client->get('/integration/product/brands', ['page' => 0]);

    expect($result)->toBeInstanceOf(ServiceResult::class);
    expect($result->ok)->toBeTrue();
    expect($result->data['content'][0]['id'])->toBe(1);
});

it('get() başarısız response ServiceResult::fail döner', function () {
    Http::fake([
        'apigw.trendyol.com/*' => Http::response(['message' => 'Unauthorized'], 401),
    ]);

    $client = new Client('key', 'secret', '1000', false);
    $result = $client->get('/integration/product/brands');

    expect($result->ok)->toBeFalse();
    expect($result->errorCode)->toBe('api_error');
    expect($result->errorMessage)->toContain('401');
});

it('rate limit aşıldığında ServiceResult::fail(\'rate_limited\') döner', function () {
    config()->set('marketplaces.trendyol.rate_limits.default.per_minute', 2);

    Http::fake([
        'apigw.trendyol.com/*' => Http::response(['content' => []], 200),
    ]);

    $client = new Client('key', 'secret', '1000', false);

    $client->get('/integration/product/brands', ['page' => 0]);
    $client->get('/integration/product/brands', ['page' => 1]);

    $result = $client->get('/integration/product/brands', ['page' => 2]);

    expect($result->ok)->toBeFalse();
    expect($result->errorCode)->toBe('rate_limited');
});

it('post() body gönderir', function () {
    Http::fake([
        'apigw.trendyol.com/*' => function ($request) {
            expect($request['items'][0]['barcode'])->toBe('123');

            return Http::response(['batchRequestId' => 'abc-123'], 200);
        },
    ]);

    $client = new Client('key', 'secret', '1000', false);
    $result = $client->post('/integration/inventory/sellers/1000/products/price-and-inventory', [
        'items' => [['barcode' => '123', 'quantity' => 5]],
    ]);

    expect($result->ok)->toBeTrue();
    expect($result->data['batchRequestId'])->toBe('abc-123');
});

it('put() body gönderir', function () {
    Http::fake([
        'apigw.trendyol.com/*' => function ($request) {
            expect($request['status'])->toBe('Picking');

            return Http::response(['success' => true], 200);
        },
    ]);

    $client = new Client('key', 'secret', '1000', false);
    $result = $client->put('/integration/oms/core/v1/shipment-packages/555', [
        'status' => 'Picking',
    ]);

    expect($result->ok)->toBeTrue();
});
