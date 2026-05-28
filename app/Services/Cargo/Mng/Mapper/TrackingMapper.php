<?php

namespace App\Services\Cargo\Mng\Mapper;

use App\Support\Enums\ShipmentStatus;

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
            ?? $response['items']
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

    public function mapStatus(string $rawStatus): string
    {
        return match (mb_strtolower($rawStatus, 'UTF-8')) {
            'kayıt', 'kayit', 'created' => ShipmentStatus::Created->value,
            'şubede', 'subede', 'kargoya verildi' => ShipmentStatus::PickedUp->value,
            'yolda', 'transfer', 'aktarma' => ShipmentStatus::InTransit->value,
            'dağıtımda', 'dagitimda' => ShipmentStatus::OutForDelivery->value,
            'teslim edildi', 'teslim', 'delivered' => ShipmentStatus::Delivered->value,
            'başarısız', 'basarisiz' => ShipmentStatus::Failed->value,
            'iade', 'geri döndü' => ShipmentStatus::Returned->value,
            'iptal' => ShipmentStatus::Cancelled->value,
            default => ShipmentStatus::InTransit->value,
        };
    }
}
