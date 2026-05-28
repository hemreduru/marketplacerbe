<?php

use App\Jobs\ProcessIncomingWebhookJob;
use App\Models\MarketplaceEvent;
use App\Models\MarketplaceListing;
use App\Models\MasterProduct;
use App\Models\UserMarketplaceCredential;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    Queue::fake();
});

it('idempotent: aynı event_uuid iki kez gelirse tek MarketplaceEvent oluşur', function () {
    $credential = UserMarketplaceCredential::factory()->create([
        'webhook_uuid' => '550e8400-e29b-41d4-a716-446655440000',
    ]);

    $payload = [
        'eventId' => 'evt-abc-123',
        'notificationType' => 'ORDER_CREATED',
        'orderNumber' => 'ORDER-9999',
        'status' => 'Created',
        'lines' => [
            ['merchantSku' => 'SKU-001', 'barcode' => '8680000000123', 'quantity' => 2],
        ],
    ];

    $this->postJson('/webhooks/trendyol/550e8400-e29b-41d4-a716-446655440000', $payload)
        ->assertNoContent(200);

    $this->postJson('/webhooks/trendyol/550e8400-e29b-41d4-a716-446655440000', $payload)
        ->assertNoContent(200);

    expect(MarketplaceEvent::count())->toBe(1);
    expect(MarketplaceEvent::first()->event_uuid)->toBe('evt-abc-123');
});

it('bilinmeyen credential UUID için 200 döner (opsiyonel)', function () {
    $this->postJson('/webhooks/trendyol/nonexistent-uuid', [
        'eventId' => 'evt-xyz',
        'notificationType' => 'ORDER_CREATED',
    ])->assertNoContent(200);

    expect(MarketplaceEvent::count())->toBe(0);
});

it('order_created webhook stok event oluşturur', function () {
    Queue::fake();

    $credential = UserMarketplaceCredential::factory()->create([
        'webhook_uuid' => '550e8400-e29b-41d4-a716-446655440001',
    ]);

    $master = MasterProduct::factory()->create([
        'user_id' => $credential->user_id,
        'current_stock' => 50,
        'version' => 0,
    ]);

    $listing = MarketplaceListing::factory()->create([
        'master_product_id' => $master->id,
        'user_marketplace_credential_id' => $credential->id,
        'remote_sku' => 'SKU-001',
        'remote_barcode' => '8680000000123',
    ]);

    $payload = [
        'eventId' => 'evt-order-stock-1',
        'notificationType' => 'ORDER_CREATED',
        'orderNumber' => 'ORDER-5555',
        'status' => 'Created',
        'packageStatus' => 'Created',
        'orderDate' => now()->getTimestampMs(),
        'lines' => [
            ['merchantSku' => 'SKU-001', 'barcode' => '8680000000123', 'quantity' => 2],
        ],
    ];

    $this->postJson('/webhooks/trendyol/550e8400-e29b-41d4-a716-446655440001', $payload)
        ->assertNoContent(200);

    expect(MarketplaceEvent::count())->toBe(1);

    // Job dispatch edilmiş mi?
    Queue::assertPushed(ProcessIncomingWebhookJob::class);
});
