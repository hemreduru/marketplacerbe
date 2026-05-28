<?php

namespace App\Services\Cargo\Aras;

use App\Support\ServiceResult;

/**
 * Aras Kargo webhook işleyici.
 *
 * Aras durum değişimlerinde webhook gönderir.
 * Bu servis gelen webhook payload'ını işler.
 */
class WebhookService
{
    /**
     * Webhook payload'ını doğrula ve işle.
     *
     * @param  array<string, mixed>  $payload
     * @return ServiceResult<array{tracking_number: string, status: string, location: ?string, timestamp: string}>
     */
    public function handle(array $payload): ServiceResult
    {
        $trackingNumber = $payload['trackingNumber']
            ?? $payload['ShipmentNumber']
            ?? $payload['barcode']
            ?? null;

        if (! $trackingNumber) {
            return ServiceResult::fail('aras_webhook_invalid', __('cargo.webhook_invalid'));
        }

        return ServiceResult::ok([
            'tracking_number' => $trackingNumber,
            'status' => $payload['status'] ?? $payload['StatusCode'] ?? 'in_transit',
            'location' => $payload['location'] ?? $payload['Location'] ?? null,
            'timestamp' => $payload['timestamp'] ?? $payload['EventDate'] ?? now()->toIso8601String(),
        ]);
    }
}
