<?php

use App\Jobs\SyncDispatcherJob;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\SyncDispatchEntry;
use App\Models\UserMarketplaceCredential;
use Illuminate\Support\Facades\Http;

it('write disabled iken status=skipped olur', function () {
    config()->set('marketplace.write_enabled', false);

    $listing = MarketplaceListing::factory()->create();
    $entry = SyncDispatchEntry::factory()->create([
        'marketplace_listing_id' => $listing->id,
        'master_product_id' => $listing->master_product_id ?? MasterProduct::factory()->create()->id,
    ]);

    (new SyncDispatcherJob($entry->id))->handle();

    expect($entry->fresh()->status)->toBe('skipped');
    expect($entry->fresh()->last_error)->toBe('write_disabled');
});

it('env write açık ama credential.write_enabled false ise skipped', function () {
    config()->set('marketplace.write_enabled', true);

    $credential = UserMarketplaceCredential::factory()->create([
        'additional_credentials' => ['seller_id' => '12345', 'write_enabled' => false],
    ]);
    $listing = MarketplaceListing::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);
    $entry = SyncDispatchEntry::factory()->create([
        'marketplace_listing_id' => $listing->id,
    ]);

    (new SyncDispatcherJob($entry->id))->handle();

    expect($entry->fresh()->status)->toBe('skipped');
});

it('iki katmanlı sigorta açıkken status=sent olur', function () {
    config()->set('marketplace.write_enabled', true);
    Http::fake(['*price-and-inventory*' => Http::response(['batchRequestId' => 'x'], 200)]);

    $credential = UserMarketplaceCredential::factory()->create([
        'additional_credentials' => ['seller_id' => '12345', 'write_enabled' => true],
    ]);
    $listing = MarketplaceListing::factory()->create([
        'user_marketplace_credential_id' => $credential->id,
    ]);
    $entry = SyncDispatchEntry::factory()->create([
        'marketplace_listing_id' => $listing->id,
    ]);

    (new SyncDispatcherJob($entry->id))->handle();

    expect($entry->fresh()->status)->toBe('sent');
    expect($entry->fresh()->attempt_count)->toBe(1);
});

it('retry policy property mirası ile gelir', function () {
    $job = new SyncDispatcherJob(0);

    expect($job->tries)->toBe(5);
    expect($job->backoff)->toBe([30, 120, 600, 3600, 21600]);
});
