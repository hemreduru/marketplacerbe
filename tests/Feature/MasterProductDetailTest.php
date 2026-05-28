<?php

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\User;
use App\Models\UserMarketplaceCredential;

it('logged-in user can view their master product detail', function () {
    $user = User::factory()->withPlan('growth')->create();

    $credential = UserMarketplaceCredential::factory()->create(['user_id' => $user->id]);

    $master = MasterProduct::factory()->create([
        'user_id' => $user->id,
        'title' => 'Test Master Product',
        'sku' => 'MST-001',
        'current_stock' => 42,
        'current_price' => 199.9900,
    ]);

    MarketplaceListing::factory()->count(2)->create([
        'master_product_id' => $master->id,
        'user_marketplace_credential_id' => $credential->id,
    ]);

    $this->actingAs($user)
        ->get(route('master-products.show', $master->id))
        ->assertOk()
        ->assertSee('Test Master Product')
        ->assertSee('MST-001')
        ->assertSee('42');
});

it('shows listing tiles for each marketplace listing', function () {
    $user = User::factory()->withPlan('growth')->create();
    $credential = UserMarketplaceCredential::factory()->create(['user_id' => $user->id]);

    $master = MasterProduct::factory()->create(['user_id' => $user->id]);

    MarketplaceListing::factory()->create([
        'master_product_id' => $master->id,
        'user_marketplace_credential_id' => $credential->id,
        'listing_status' => 'active',
        'sync_status' => 'synced',
        'remote_sku' => 'RMT-001',
    ]);

    $this->actingAs($user)
        ->get(route('master-products.show', $master->id))
        ->assertOk()
        ->assertSee('RMT-001')
        ->assertSee('synced')
        ->assertSee('active');
});
