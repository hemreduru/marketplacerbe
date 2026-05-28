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
 * Trendyol webhook alımı — POST /webhooks/trendyol/{credentialUuid}
 *
 * İdempotent: aynı event_uuid ikinci kez gelirse duplicate olarak skip eder,
 * her değişikliğe "200 OK" döner (Trendyol'un retry mekanizmasını tetiklememek için).
 */
class WebhookController extends Controller
{
    public function __invoke(Request $request, string $credentialUuid): Response
    {
        $payload = $request->all();

        Log::debug('Trendyol webhook received', [
            'uuid' => $credentialUuid,
            'event_type' => $payload['notificationType'] ?? 'unknown',
        ]);

        $credential = UserMarketplaceCredential::where('webhook_uuid', $credentialUuid)
            ->where('is_active', true)
            ->first();

        if (! $credential) {
            Log::warning('Trendyol webhook: credential bulunamadı', ['uuid' => $credentialUuid]);

            return response()->noContent(200);
        }

        // IP allowlist denetimi: yapılandırılmışsa zorla, yoksa geriye dönük uyumlu olarak izin ver.
        if (! $this->isIpAllowed($request->ip())) {
            Log::warning('Trendyol webhook: IP izin verilmedi — istek engellendi', [
                'ip' => $request->ip(),
                'uuid' => $credentialUuid,
            ]);

            return response()->noContent(403);
        }

        // İdempotent event UUID: Trendyol'un gönderdiği eventId varsa onu,
        // yoksa payload'daki tanımlayıcı alanlardan deterministik hash kullan.
        $eventUuid = $payload['eventId']
            ?? md5(
                ($payload['orderNumber'] ?? $payload['claimId'] ?? 'fallback')
                .'|'.($payload['notificationType'] ?? 'unknown')
                .'|'.($payload['status'] ?? $payload['orderStatus'] ?? $payload['claimStatus'] ?? '')
            );

        $eventType = match ($payload['notificationType'] ?? '') {
            'ORDER_CREATED', 'ORDER_STATUS_CHANGED' => 'order_status_changed',
            'CLAIM_CREATED', 'CLAIM_STATUS_CHANGED' => 'claim_status_changed',
            'RETURN_PACKAGE_STATUS_CHANGED' => 'return_status_changed',
            default => 'unknown',
        };

        $sourceReference = $payload['orderNumber']
            ?? $payload['claimId']
            ?? null;

        $event = MarketplaceEvent::firstOrCreate(
            ['event_uuid' => $eventUuid],
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
            Log::info('Trendyol webhook: duplicate event skipped', ['event_uuid' => $eventUuid]);

            return response()->noContent(200);
        }

        ProcessIncomingWebhookJob::dispatch($event->id);

        return response()->noContent(200);
    }

    /**
     * Webhook kaynağının IP'si izin listesinde mi?
     *
     * İzin listesi boşsa (yapılandırılmamışsa) geriye dönük uyumluluk için
     * true döner ve bir uyarı log'lar. Yapılandırılmışsa yalnızca listedeki
     * IP'lere izin verir.
     */
    protected function isIpAllowed(string $ip): bool
    {
        $allowedIps = config('marketplaces.trendyol.webhook.allowed_ips', []);

        if (empty($allowedIps)) {
            Log::warning('Trendyol webhook: IP izin listesi yapılandırılmamış, tüm IP\'lere izin veriliyor.');

            return true;
        }

        return in_array($ip, $allowedIps, true);
    }
}
