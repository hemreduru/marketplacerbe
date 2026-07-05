<?php

use App\Jobs\SyncDispatcherJob;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\SyncDispatchEntry;
use Illuminate\Support\Facades\Http;

/**
 * Write-dispatch: iki-katmanlı sigorta (MARKETPLACE_WRITE_ENABLED env +
 * credential write_enabled). Tüm testler Http::fake — CANLI API'ye gidilmez
 * (seed'deki Trendyol anahtarı gerçek mağaza).
 */
function dispatchEntryFor(bool $credentialWriteEnabled): SyncDispatchEntry
{
    [$user, $credential] = userWithTrendyol(credentialAttributes: [
        'additional_credentials' => ['seller_id' => '342591', 'write_enabled' => $credentialWriteEnabled],
    ]);
    $master = MasterProduct::factory()->create(['user_id' => $user->id]);
    $listing = MarketplaceListing::factory()->create([
        'master_product_id' => $master->id,
        'user_marketplace_credential_id' => $credential->id,
        'remote_barcode' => 'BC-1',
    ]);

    return SyncDispatchEntry::create([
        'master_product_id' => $master->id,
        'marketplace_listing_id' => $listing->id,
        'mutation_type' => 'price',
        'payload_json' => ['listed_price' => 199.90],
        'status' => 'pending',
        'attempt_count' => 0,
    ]);
}

test('global write kapalıyken atlanır ve HİÇBİR HTTP çağrısı yapılmaz', function () {
    Http::fake();
    // marketplace.write_enabled = false (test varsayılanı)
    $entry = dispatchEntryFor(credentialWriteEnabled: true);

    SyncDispatcherJob::dispatchSync($entry->id);

    expect($entry->fresh()->status)->toBe('skipped');
    Http::assertNothingSent();
});

test('global açık ama credential write_enabled kapalıysa atlanır (iki-katmanlı sigorta)', function () {
    config(['marketplace.write_enabled' => true]);
    Http::fake();
    $entry = dispatchEntryFor(credentialWriteEnabled: false);

    SyncDispatcherJob::dispatchSync($entry->id);

    expect($entry->fresh()->status)->toBe('skipped');
    Http::assertNothingSent();
});

test('her iki katman açıkken Trendyol price-and-inventory çağrılır', function () {
    config(['marketplace.write_enabled' => true]);
    Http::fake(['*price-and-inventory*' => Http::response(['batchRequestId' => 'x'], 200)]);
    $entry = dispatchEntryFor(credentialWriteEnabled: true);

    SyncDispatcherJob::dispatchSync($entry->id);

    expect($entry->fresh()->status)->toBe('sent');
    Http::assertSent(fn ($request) => str_contains($request->url(), 'price-and-inventory'));
});

test('pazaryeri API hatasında yeniden denenir (pending)', function () {
    config(['marketplace.write_enabled' => true]);
    Http::fake(['*price-and-inventory*' => Http::response(['errorMessage' => 'rejected'], 400)]);
    $entry = dispatchEntryFor(credentialWriteEnabled: true);

    SyncDispatcherJob::dispatchSync($entry->id);

    expect($entry->fresh()->status)->toBe('pending')
        ->and($entry->fresh()->attempt_count)->toBe(1);
});
