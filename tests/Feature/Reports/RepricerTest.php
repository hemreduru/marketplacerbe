<?php

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\PriceEvent;
use App\Models\RepricerRule;
use App\Models\SyncDispatchEntry;
use App\Services\Repricer\RepricerService;
use App\Support\Enums\PriceEventType;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('target_margin kuralı maliyet üzeri fiyat hesaplayıp dispatch oluşturur', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create([
        'user_id' => $user->id, 'cost_price' => 100, 'current_price' => 100,
    ]);
    MarketplaceListing::factory()->create([
        'user_marketplace_credential_id' => $credential->id, 'master_product_id' => $master->id,
    ]);

    RepricerRule::factory()->create([
        'user_id' => $user->id, 'master_product_id' => $master->id,
        'strategy' => 'target_margin', 'target_margin' => 50, 'min_price' => 1, 'max_price' => 1000,
    ]);

    $result = app(RepricerService::class)->run($user);

    // 100 × 1.50 = 150 → değişti → 1 dispatch
    expect($result['dispatched'])->toBe(1);
    expect(SyncDispatchEntry::where('master_product_id', $master->id)->where('mutation_type', 'price')->count())->toBe(1);

    $event = PriceEvent::where('master_product_id', $master->id)->first();
    expect($event)->not->toBeNull()
        ->and($event->event_type->value)->toBe(PriceEventType::StrategyRecompute->value)
        ->and((float) $event->new_price)->toBe(150.0);
});

test('15 dakika cooldown içinde ikinci çalıştırma fiyat değiştirmez', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create(['user_id' => $user->id, 'cost_price' => 100, 'current_price' => 100]);
    MarketplaceListing::factory()->create(['user_marketplace_credential_id' => $credential->id, 'master_product_id' => $master->id]);
    RepricerRule::factory()->create(['user_id' => $user->id, 'master_product_id' => $master->id, 'strategy' => 'target_margin', 'target_margin' => 50, 'min_price' => 1, 'max_price' => 1000]);

    $service = app(RepricerService::class);
    $service->run($user);
    $second = $service->run($user);

    // cooldown nedeniyle ikinci çalıştırma dispatch oluşturmaz
    expect($second['dispatched'])->toBe(0)
        ->and(PriceEvent::where('master_product_id', $master->id)->count())->toBe(1);
});

test('max_price clamp uygulanır', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $master = MasterProduct::factory()->create(['user_id' => $user->id, 'cost_price' => 100, 'current_price' => 100]);
    MarketplaceListing::factory()->create(['user_marketplace_credential_id' => $credential->id, 'master_product_id' => $master->id]);
    RepricerRule::factory()->create(['user_id' => $user->id, 'master_product_id' => $master->id, 'strategy' => 'target_margin', 'target_margin' => 50, 'min_price' => 1, 'max_price' => 120]);

    app(RepricerService::class)->run($user);

    // 150 hesaplandı ama max_price 120'ye clamp edildi
    expect((float) PriceEvent::where('master_product_id', $master->id)->first()->new_price)->toBe(120.0);
});

test('repricer rule CRUD ve run sayfası çalışır', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $this->actingAs($user)->get(route('repricer.index'))->assertOk();

    $this->actingAs($user)->post(route('repricer.store'), [
        'name' => 'Test Rule', 'strategy' => 'fixed', 'min_price' => 10, 'max_price' => 100, 'is_active' => 1,
    ])->assertRedirect();

    expect(RepricerRule::where('user_id', $user->id)->count())->toBe(1);

    $rule = RepricerRule::first();
    $this->actingAs($user)->delete(route('repricer.destroy', $rule))->assertRedirect();
    expect(RepricerRule::count())->toBe(0);
});
