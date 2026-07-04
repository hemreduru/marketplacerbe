<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessIncomingWebhookJob;
use App\Models\MarketplaceEvent;
use App\Models\UserMarketplaceCredential;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Hepsiburada webhook alımı — POST /webhooks/hepsiburada/{credentialUuid}
 *
 * HB, satıcının tanımladığı endpoint'i Basic Auth ile çağırır (Sipariş + Talep
 * webhook'ları, at-least-once teslimat). İdempotency: event_uuid UNIQUE —
 * duplicate teslimatlar sessizce atlanır. Her durumda 200 dönülür ki HB
 * retry fırtınası tetiklenmesin.
 */
class HepsiburadaWebhookController extends Controller
{
    public function __invoke(Request $request, string $credentialUuid): Response
    {
        $payload = $request->all();

        $credential = UserMarketplaceCredential::where('webhook_uuid', $credentialUuid)
            ->where('is_active', true)
            ->first();

        if (! $credential) {
            Log::warning('HB webhook: credential bulunamadı', ['uuid' => $credentialUuid]);

            return response()->noContent(200);
        }

        // Opsiyonel Basic Auth doğrulaması: credential'da secret tanımlıysa zorla
        if (! $this->isAuthorized($request, $credential)) {
            Log::warning('HB webhook: basic auth doğrulaması başarısız', ['uuid' => $credentialUuid]);

            return response()->noContent(401);
        }

        // İdempotent event UUID: HB id'si varsa onu, yoksa deterministik hash
        $eventUuid = $payload['id']
            ?? $payload['eventId']
            ?? md5(
                ($payload['orderNumber'] ?? $payload['orderId'] ?? $payload['claimNumber'] ?? 'fallback')
                .'|'.($payload['eventType'] ?? $payload['type'] ?? 'unknown')
                .'|'.($payload['status'] ?? '')
            );

        $eventType = $this->resolveEventType($payload);

        $sourceReference = $payload['orderNumber']
            ?? $payload['orderId']
            ?? $payload['claimNumber']
            ?? null;

        $event = MarketplaceEvent::firstOrCreate(
            ['event_uuid' => (string) $eventUuid],
            [
                'user_marketplace_credential_id' => $credential->id,
                'marketplace_code' => $credential->marketplace->slug,
                'event_type' => $eventType,
                'source_reference' => (string) $sourceReference,
                'raw_payload' => $payload,
                'status' => 'received',
            ]
        );

        if (! $event->wasRecentlyCreated) {
            Log::info('HB webhook: duplicate event skipped', ['event_uuid' => $eventUuid]);

            return response()->noContent(200);
        }

        ProcessIncomingWebhookJob::dispatch($event->id);

        return response()->noContent(200);
    }

    /**
     * HB webhook Basic Auth denetimi.
     *
     * `additional_credentials.webhook_secret` tanımlıysa gelen isteğin Basic Auth
     * şifresi bununla eşleşmek zorundadır; tanımlı değilse geriye dönük uyumlu
     * olarak izin verilir (uyarı log'lanır).
     */
    protected function isAuthorized(Request $request, UserMarketplaceCredential $credential): bool
    {
        $secret = $credential->additional_credentials['webhook_secret'] ?? null;

        if ($secret === null || $secret === '') {
            Log::warning('HB webhook: webhook_secret yapılandırılmamış, auth denetimi atlanıyor.');

            return true;
        }

        return hash_equals((string) $secret, (string) $request->getPassword());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function resolveEventType(array $payload): string
    {
        $type = mb_strtolower((string) ($payload['eventType'] ?? $payload['type'] ?? ''), 'UTF-8');

        return match (true) {
            str_contains($type, 'order') => 'order_status_changed',
            str_contains($type, 'claim'), str_contains($type, 'talep') => 'claim_status_changed',
            str_contains($type, 'return'), str_contains($type, 'iade') => 'return_status_changed',
            isset($payload['orderNumber']) || isset($payload['orderId']) => 'order_status_changed',
            default => 'unknown',
        };
    }
}
