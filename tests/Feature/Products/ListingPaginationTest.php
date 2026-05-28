<?php

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('products list sayfasını yetkili kullanıcı açabilir', function () {
    [$user] = userWithTrendyol();

    $this->actingAs($user)
        ->get(route('products.index'))
        ->assertOk();
});

test('products.data DataTables formatında JSON döner ve length parametresine uyar', function () {
    [$user, $credential] = userWithTrendyol();

    Product::factory()->count(25)->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('products.data', ['draw' => 1, 'start' => 0, 'length' => 10]));

    $response->assertOk();
    $payload = $response->json();

    expect($payload)->toHaveKeys(['draw', 'recordsTotal', 'recordsFiltered', 'data'])
        ->and($payload['recordsTotal'])->toBe(25)
        ->and(count($payload['data']))->toBe(10);
});

test('products.data search parametresi recordsFiltered\'i daraltır', function () {
    [$user, $credential] = userWithTrendyol();

    Product::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'title' => 'Eşsiz Mavi Tişört',
        'sku' => 'BLUE-SHIRT',
    ]);
    Product::factory()->count(5)->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('products.data', [
            'draw' => 1,
            'start' => 0,
            'length' => 10,
            'search' => ['value' => 'Mavi'],
        ]));

    $response->assertOk();
    $payload = $response->json();

    expect($payload['recordsTotal'])->toBe(6)
        ->and($payload['recordsFiltered'])->toBe(1);
});

test('başka kullanıcının ürünleri DataTables\'a sızmaz', function () {
    [$user, $credential] = userWithTrendyol();
    [$otherUser, $otherCredential] = userWithTrendyol();

    Product::factory()->count(3)->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);
    Product::factory()->count(7)->create([
        'user_marketplace_credential_id' => $otherCredential->id,
    ]);

    $response = $this->actingAs($user)
        ->get(route('products.data', ['draw' => 1, 'start' => 0, 'length' => 50]));

    $payload = $response->json();
    expect($payload['recordsTotal'])->toBe(3);
});
