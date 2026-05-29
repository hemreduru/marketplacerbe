<?php

use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Services\Buybox\BuyboxService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function listingForUser($user, $credential): MarketplaceListing
{
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);

    return MarketplaceListing::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
        'master_product_id' => $master->id,
    ]);
}

test('buybox tracker son snapshot durumunu gösterir ve kaybı sayar', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $listing = listingForUser($user, $credential);
    $service = app(BuyboxService::class);

    $service->record($listing, ['has_buybox' => true, 'our_price' => 100, 'checked_at' => now()->subHour()]);
    $service->record($listing, ['has_buybox' => false, 'our_price' => 100, 'competitor_price' => 95, 'checked_at' => now()]);

    $rows = $service->trackerRows($user);
    expect($rows)->toHaveCount(1)
        ->and($rows->first()['has_buybox'])->toBeFalse();

    expect($service->lostBuybox($user))->toHaveCount(1);
});

test('buybox sayfası render olur', function () {
    [$user, $credential] = userWithTrendyol('pro');
    $this->actingAs($user)->get(route('reports.buybox'))->assertOk();
});

test('buybox sync endpoint config yoksa graceful döner', function () {
    [$user, $credential] = userWithTrendyol('pro');

    $this->actingAs($user)
        ->post(route('reports.buybox.sync'))
        ->assertRedirect();
});
