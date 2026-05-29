<?php

use App\Exceptions\SubscriptionLimitException;
use App\Models\Marketplace;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Services\Marketplaces\Trendyol\Client as TrendyolClient;
use App\Services\Marketplaces\Trendyol\OrderService;
use App\Services\Marketplaces\Trendyol\ProductService;
use Illuminate\Support\Facades\Http;

test('starter plan restricts adding more than one marketplace connection', function () {
    $user = User::factory()->withPlan('starter')->create();

    $trendyol = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();

    $hepsiburada = Marketplace::where('slug', 'hepsiburada')->first()
        ?? Marketplace::factory()->create(['slug' => 'hepsiburada', 'name' => 'Hepsiburada']);

    UserMarketplaceCredential::factory()->create([
        'user_id' => $user->id,
        'marketplace_id' => $trendyol->id,
    ]);

    $response = $this->actingAs($user)
        ->putJson(route('marketplace.settings.update'), [
            'marketplace_id' => $hepsiburada->id,
            'api_key' => 'hb-api-key',
            'api_secret' => 'hb-api-secret',
            'additional_credentials' => ['seller_id' => 'hb-seller'],
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => __('subscription.marketplace_limit_reached', ['limit' => 1]),
        ]);
});

test('growth plan allows connecting up to three marketplaces but blocks fourth', function () {
    $user = User::factory()->withPlan('growth')->create();

    $trendyol = Marketplace::where('slug', 'trendyol')->first() ?? Marketplace::factory()->trendyol()->create();
    $hepsiburada = Marketplace::where('slug', 'hepsiburada')->first() ?? Marketplace::factory()->create(['slug' => 'hepsiburada', 'name' => 'Hepsiburada']);
    $n11 = Marketplace::where('slug', 'n11')->first() ?? Marketplace::factory()->create(['slug' => 'n11', 'name' => 'n11']);
    $amazon = Marketplace::where('slug', 'amazon')->first() ?? Marketplace::factory()->create(['slug' => 'amazon', 'name' => 'Amazon']);

    UserMarketplaceCredential::factory()->create(['user_id' => $user->id, 'marketplace_id' => $trendyol->id]);
    UserMarketplaceCredential::factory()->create(['user_id' => $user->id, 'marketplace_id' => $hepsiburada->id]);
    UserMarketplaceCredential::factory()->create(['user_id' => $user->id, 'marketplace_id' => $n11->id]);

    $response = $this->actingAs($user)
        ->putJson(route('marketplace.settings.update'), [
            'marketplace_id' => $amazon->id,
            'api_key' => 'amz-api-key',
            'api_secret' => 'amz-api-secret',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => __('subscription.marketplace_limit_reached', ['limit' => 3]),
        ]);
});

test('starter plan restricts manual product sync when product limit is reached', function () {
    [$user, $credential] = userWithTrendyol('starter');

    Product::factory()->count(500)->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);

    $response = $this->actingAs($user)
        ->postJson(route('products.sync'));

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => __('subscription.product_limit_reached', ['limit' => 500]),
        ]);
});

test('product sync service throws exception when limit is hit during loop', function () {
    [$user, $credential] = userWithTrendyol('starter');

    Product::factory()->count(500)->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);

    Http::fake([
        '*/integration/product/sellers/*/products*' => Http::response([
            'content' => [
                ['id' => 9999, 'barcode' => 'new-barcode', 'title' => 'New Product', 'salePrice' => 100],
            ],
            'totalElements' => 1,
        ]),
    ]);

    $service = new ProductService(new TrendyolClient('key', 'secret', 'seller'));

    $thrown = false;
    try {
        $service->syncProducts($credential->id);
    } catch (SubscriptionLimitException $e) {
        $thrown = true;
        expect($e->getMessage())->toBe(__('subscription.product_limit_reached', ['limit' => 500]));
    }

    expect($thrown)->toBeTrue();
});

test('order sync service throws exception when monthly order limit is reached', function () {
    [$user, $credential] = userWithTrendyol('starter');

    Order::factory()->count(100)->create([
        'user_id' => $user->id,
        'marketplace_id' => $credential->marketplace_id,
        'order_date' => now()->startOfMonth()->addDays(2),
    ]);

    $service = new OrderService(new TrendyolClient('key', 'secret', 'seller'));

    $thrown = false;
    try {
        $service->syncOrders($credential->marketplace_id, $user->id);
    } catch (SubscriptionLimitException $e) {
        $thrown = true;
        expect($e->getMessage())->toBe(__('subscription.order_limit_reached', ['limit' => 100]));
    }

    expect($thrown)->toBeTrue();
});

test('analytics and claims routes redirect starter users but allow growth and pro users', function () {
    $marketplace = Marketplace::where('slug', 'trendyol')->first()
        ?? Marketplace::factory()->trendyol()->create();

    $starterUser = User::factory()->withPlan('starter')->create();
    UserMarketplaceCredential::factory()->create(['user_id' => $starterUser->id, 'marketplace_id' => $marketplace->id]);

    $proUser = User::factory()->withPlan('pro')->create();
    UserMarketplaceCredential::factory()->create(['user_id' => $proUser->id, 'marketplace_id' => $marketplace->id]);

    // Starter — gated
    $this->actingAs($starterUser)
        ->get(route('financial.index'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', __('subscription.analytics_restricted'));

    $this->actingAs($starterUser)
        ->get(route('claims.index'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('error', __('subscription.claims_restricted'));

    // Pro — allowed
    $this->actingAs($proUser)
        ->get(route('financial.index'))
        ->assertOk();

    $this->actingAs($proUser)
        ->get(route('claims.index'))
        ->assertOk();
});

test('pro plan allows unlimited marketplace connections', function () {
    $user = User::factory()->withPlan('pro')->create();

    $trendyol = Marketplace::where('slug', 'trendyol')->first() ?? Marketplace::factory()->trendyol()->create();
    $hepsiburada = Marketplace::where('slug', 'hepsiburada')->first() ?? Marketplace::factory()->create(['slug' => 'hepsiburada', 'name' => 'Hepsiburada']);
    $n11 = Marketplace::where('slug', 'n11')->first() ?? Marketplace::factory()->create(['slug' => 'n11', 'name' => 'n11']);
    $amazon = Marketplace::where('slug', 'amazon')->first() ?? Marketplace::factory()->create(['slug' => 'amazon', 'name' => 'Amazon']);

    UserMarketplaceCredential::factory()->create(['user_id' => $user->id, 'marketplace_id' => $trendyol->id]);
    UserMarketplaceCredential::factory()->create(['user_id' => $user->id, 'marketplace_id' => $hepsiburada->id]);
    UserMarketplaceCredential::factory()->create(['user_id' => $user->id, 'marketplace_id' => $n11->id]);

    $response = $this->actingAs($user)
        ->putJson(route('marketplace.settings.update'), [
            'marketplace_id' => $amazon->id,
            'api_key' => 'amz-api-key',
            'api_secret' => 'amz-api-secret',
        ]);

    $response->assertStatus(200)
        ->assertJson(['success' => true]);
});
