<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Support\ServiceResult;

/**
 * Trendyol webhook alımı (PR 1.10'da doldurulacak).
 *
 * Trendyol sipariş durumu, iade gibi olayları Cirotik'e
 * `POST /webhooks/trendyol/{credentialUuid}` üzerinden bildirir.
 */
class WebhookService
{
    public function __construct(protected Client $client) {}

    /**
     * Gelen webhook payload'unu doğrula.
     *
     * @param  array<string, mixed>  $payload
     */
    public function validatePayload(array $payload): ServiceResult
    {
        if (empty($payload['orderNumber'] ?? null)) {
            return ServiceResult::fail(
                'invalid_payload',
                'orderNumber alanı zorunludur.',
            );
        }

        return ServiceResult::ok($payload);
    }

    /**
     * Webhook event tipini çöz.
     *
     * @return string|null package, claim, question
     */
    public function resolveEventType(array $payload): ?string
    {
        if (isset($payload['packageStatus'])) {
            return 'package';
        }

        if (isset($payload['claimStatus'])) {
            return 'claim';
        }

        return null;
    }
}
