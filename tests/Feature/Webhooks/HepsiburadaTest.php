<?php

use App\Jobs\ProcessIncomingWebhookJob;
use App\Models\Marketplace;
use App\Models\MarketplaceEvent;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use Illuminate\Support\Facades\Queue;

function hbWebhookCredential(array $extra = []): UserMarketplaceCredential
{
    $marketplace = Marketplace::where('slug', 'hepsiburada')->first()
        ?? Marketplace::factory()->hepsiburada()->create();

    $user = User::factory()->withPlan('growth')->create();

    return UserMarketplaceCredential::factory()->create(array_merge([
        'user_id' => $user->id,
        'marketplace_id' => $marketplace->id,
        'is_active' => true,
        'additional_credentials' => ['seller_id' => 'hb-seller-1'],
    ], $extra));
}

test('HB sipariş webhooku MarketplaceEvent yaratır ve job dispatch eder', function () {
    Queue::fake();
    $credential = hbWebhookCredential();

    $response = $this->postJson("/webhooks/hepsiburada/{$credential->webhook_uuid}", [
        'id' => 'hb-evt-1',
        'eventType' => 'OrderCreated',
        'orderNumber' => 'HB-9001',
        'status' => 'Created',
    ]);

    $response->assertStatus(200);

    $event = MarketplaceEvent::where('event_uuid', 'hb-evt-1')->first();
    expect($event)->not->toBeNull()
        ->and($event->marketplace_code)->toBe('hepsiburada')
        ->and($event->event_type)->toBe('order_status_changed')
        ->and($event->source_reference)->toBe('HB-9001');

    Queue::assertPushed(ProcessIncomingWebhookJob::class, 1);
});

test('HB webhook duplicate teslimatta ikinci event yaratmaz', function () {
    Queue::fake();
    $credential = hbWebhookCredential();

    $payload = [
        'id' => 'hb-evt-dup',
        'eventType' => 'OrderCreated',
        'orderNumber' => 'HB-9002',
        'status' => 'Created',
    ];

    $this->postJson("/webhooks/hepsiburada/{$credential->webhook_uuid}", $payload)->assertStatus(200);
    $this->postJson("/webhooks/hepsiburada/{$credential->webhook_uuid}", $payload)->assertStatus(200);

    expect(MarketplaceEvent::where('event_uuid', 'hb-evt-dup')->count())->toBe(1);
    Queue::assertPushed(ProcessIncomingWebhookJob::class, 1);
});

test('HB webhook webhook_secret tanımlıysa yanlış şifreyi 401 ile reddeder', function () {
    Queue::fake();
    $credential = hbWebhookCredential([
        'additional_credentials' => ['seller_id' => 'hb-seller-1', 'webhook_secret' => 'top-secret'],
    ]);

    // Yanlış şifre → 401, event yok
    $this->postJson("/webhooks/hepsiburada/{$credential->webhook_uuid}", [
        'id' => 'hb-evt-auth',
        'orderNumber' => 'HB-9003',
    ], ['Authorization' => 'Basic '.base64_encode('hb:wrong')])->assertStatus(401);

    expect(MarketplaceEvent::count())->toBe(0);

    // Doğru şifre → 200 + event
    $this->postJson("/webhooks/hepsiburada/{$credential->webhook_uuid}", [
        'id' => 'hb-evt-auth',
        'orderNumber' => 'HB-9003',
    ], ['Authorization' => 'Basic '.base64_encode('hb:top-secret')])->assertStatus(200);

    expect(MarketplaceEvent::where('event_uuid', 'hb-evt-auth')->count())->toBe(1);
});

test('HB webhook bilinmeyen credential uuid için sessizce 200 döner', function () {
    Queue::fake();

    $this->postJson('/webhooks/hepsiburada/nonexistent-uuid', [
        'orderNumber' => 'HB-9004',
    ])->assertStatus(200);

    expect(MarketplaceEvent::count())->toBe(0);
    Queue::assertNothingPushed();
});
