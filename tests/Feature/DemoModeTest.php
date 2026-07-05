<?php

use App\Models\Marketplace;
use App\Models\MasterProduct;
use App\Models\User;
use App\Services\Demo\DemoDataService;

test('demo verisi yüklenince hesap dolar ve dashboard erişilir', function () {
    Marketplace::factory()->trendyol()->create();
    $user = User::factory()->withPlan('growth')->create(); // abone, credential yok

    $this->actingAs($user)->post(route('demo.load'))->assertRedirect(route('dashboard'));

    expect($user->marketplaceCredentials()->where('is_demo', true)->exists())->toBeTrue()
        ->and(MasterProduct::where('user_id', $user->id)->count())->toBe(4);

    // Demo credential sayesinde dashboard bağlan-uyarısı yerine veriyi gösterir.
    $this->actingAs($user)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee(__('dashboard.connect_now'));
});

test('demo populate idempotenttir', function () {
    Marketplace::factory()->trendyol()->create();
    $user = User::factory()->withPlan('growth')->create();
    $service = app(DemoDataService::class);

    $service->populate($user);
    $service->populate($user);

    expect($user->marketplaceCredentials()->where('is_demo', true)->count())->toBe(1)
        ->and(MasterProduct::where('user_id', $user->id)->count())->toBe(4);
});

test('demo verisi temizlenebilir', function () {
    Marketplace::factory()->trendyol()->create();
    $user = User::factory()->withPlan('growth')->create();
    app(DemoDataService::class)->populate($user);

    $this->actingAs($user)->post(route('demo.clear'))->assertRedirect();

    expect($user->marketplaceCredentials()->where('is_demo', true)->exists())->toBeFalse()
        ->and(MasterProduct::where('user_id', $user->id)->where('sku', 'like', 'DEMO-%')->count())->toBe(0);
});
