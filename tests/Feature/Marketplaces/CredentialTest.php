<?php

use App\Jobs\SyncTrendyolProductsJob;
use App\Models\Marketplace;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('kullanıcı yeni Trendyol credential ekleyebilir ve sync job\'lar tetiklenir', function () {
    Queue::fake();
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();
    $user = User::factory()->withPlan('growth')->create();
    $user->marketplaceCredentials()->delete();

    $response = $this->actingAs($user)
        ->put(route('marketplace.settings.update'), [
            'marketplace_id' => $marketplace->id,
            'api_key' => 'test-key',
            'api_secret' => 'test-secret',
            'additional_credentials' => ['seller_id' => '123'],
        ]);

    $response->assertOk()->assertJson(['success' => true]);

    $credential = UserMarketplaceCredential::where('user_id', $user->id)
        ->where('marketplace_id', $marketplace->id)
        ->first();

    expect($credential)->not->toBeNull()
        ->and($credential->api_key)->toBe('test-key')
        ->and($credential->is_active)->toBeTrue()
        ->and($credential->additional_credentials)->toBe(['seller_id' => '123']);

    Queue::assertPushed(SyncTrendyolProductsJob::class);
});

test('credential ekleme api_key olmadan reddedilir', function () {
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();
    $user = User::factory()->withPlan('growth')->create();

    $this->actingAs($user)
        ->put(route('marketplace.settings.update'), [
            'marketplace_id' => $marketplace->id,
            // api_key kasten eksik
            'api_secret' => 'x',
        ])->assertSessionHasErrors('api_key');
});

test('credential limiti aşılınca eklenmez', function () {
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();
    $secondMp = Marketplace::factory()->create(['slug' => 'limit-test-mp']);

    $user = User::factory()->withPlan('starter')->create();
    // Starter planda marketplace limiti 1 (config/seeder'a göre); önce mevcut credential'ı tek bırak
    $user->marketplaceCredentials()->delete();
    UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
    ]);

    $limit = $user->getSubscriptionLimit('marketplaces');
    if ($limit === -1) {
        // Plan limitsizse skip
        $this->markTestSkipped('starter plan limitsiz olarak yapılandırılmış');
    }

    $response = $this->actingAs($user)
        ->put(route('marketplace.settings.update'), [
            'marketplace_id' => $secondMp->id,
            'api_key' => 'x',
            'api_secret' => 'y',
        ]);

    // Limite ulaşıldıysa 422, değilse 200; planın gerçek değerine güveniyoruz
    if ($limit >= 1) {
        $response->assertStatus(422);
    }
});

test('pasif pazaryerine credential eklenemez (is_active guard)', function () {
    $inactive = Marketplace::factory()->create(['slug' => 'pasif-mp', 'is_active' => false]);
    $user = User::factory()->withPlan('pro')->create(); // limitsiz plan — sadece is_active guard'ı test et

    $response = $this->actingAs($user)
        ->putJson(route('marketplace.settings.update'), [
            'marketplace_id' => $inactive->id,
            'api_key' => 'x',
            'api_secret' => 'y',
        ]);

    $response->assertStatus(422)->assertJson(['success' => false]);
    expect(UserMarketplaceCredential::where('marketplace_id', $inactive->id)->exists())->toBeFalse();
});
