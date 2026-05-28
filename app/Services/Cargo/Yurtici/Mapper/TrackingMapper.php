<?php

namespace App\Services\Cargo\Yurtici\Mapper;

use App\Support\Enums\ShipmentStatus;

/**
 * Yurtici SOAP tracking response -> Cirotik formatına dönüşüm.
 */
class TrackingMapper
{
    /**
     * @param  array<string, mixed>  $response
     * @return array{status: string, location: ?string, timestamp: string}
     */
    public function toTrackingResult(array $response): array
    {
        $movements = $response['movements']
            ?? $response['trackingResult']['movements']
            ?? $response['result']['movements']
            ?? [];

        $latest = is_array($movements) ? ($movements[0] ?? []) : [];

        return [
            'status' => $this->mapStatus($latest['status'] ?? ''),
            'location' => $latest['location'] ?? null,
            'timestamp' => $latest['timestamp'] ?? now()->toIso8601String(),
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array{tracking_number: string, status: string, location: ?string, timestamp: string}>
     */
    public function toStatusList(array $response): array
    {
        $items = $response['shipmentStatusList']
            ?? $response['trackingListResult']['items']
            ?? $response['result']['items']
            ?? [];

        if (! is_array($items) || array_is_list($items) === false) {
            return [];
        }

        return array_map(function (array $item): array {
            return [
                'tracking_number' => $item['trackingNumber'] ?? '',
                'status' => $this->mapStatus($item['status'] ?? ''),
                'location' => $item['location'] ?? null,
                'timestamp' => $item['timestamp'] ?? now()->toIso8601String(),
            ];
        }, $items);
    }

    /**
     * Yurtici durum kodunu ShipmentStatus enum'una dönüştür.
     */
    public function mapStatus(string $rawStatus): string
    {
        return match (mb_strtolower($rawStatus, 'UTF-8')) {
            'kayıt', 'kayit', 'kaydedildi', 'created' => ShipmentStatus::Created->value,
            'şubede', 'subede', 'subeye ulasti', 'picked_up' => ShipmentStatus::PickedUp->value,
            'yolda', 'transfer', 'aktarma', 'in_transit' => ShipmentStatus::InTransit->value,
            'dağıtımda', 'dagitimda', 'out_for_delivery' => ShipmentStatus::OutForDelivery->value,
            'teslim', 'teslim edildi', 'delivered' => ShipmentStatus::Delivered->value,
            'başarısız', 'basarisiz', 'iadeli', 'failed' => ShipmentStatus::Failed->value,
            'iade', 'geri döndü', 'returned' => ShipmentStatus::Returned->value,
            'iptal', 'cancelled' => ShipmentStatus::Cancelled->value,
            default => ShipmentStatus::InTransit->value,
        };
    }
}
