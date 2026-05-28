<?php

namespace Tests\Unit\Cargo;

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;

class FakeArasProvider implements CargoProvider
{
    public function __construct(public CargoCredential $credential) {}

    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        return ServiceResult::ok(['tracking_number' => 'AR'.rand(1000000, 9999999), 'label_url' => null]);
    }

    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        return ServiceResult::ok(null);
    }

    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult
    {
        return ServiceResult::ok('fake-pdf-data');
    }

    public function track(string $trackingNumber): ServiceResult
    {
        return ServiceResult::ok(['status' => 'delivered', 'location' => null, 'timestamp' => now()->toIso8601String()]);
    }

    public function listStatusUpdates(\DateTimeInterface $since): ServiceResult
    {
        return ServiceResult::ok([]);
    }

    public function getServiceCode(): string
    {
        return 'aras';
    }

    public function getCapabilities(): array
    {
        return [
            'webhook' => true,
            'cod' => true,
            'label_zpl' => true,
            'label_a4' => true,
            'tracking_batch' => false,
        ];
    }
}
