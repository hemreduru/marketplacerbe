<?php

namespace App\Services\Cargo\Ups;

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;
use DateTimeInterface;

/**
 * PR 4.10 — UPS Türkiye (REST, e-ihracat). Scaffold; canlı entegrasyon ileri PR.
 */
class UpsService implements CargoProvider
{
    public function __construct(CargoCredential $credential)
    {
        // UPS REST OAuth entegrasyonu henüz aktif değil
    }

    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        return ServiceResult::fail('ups_not_implemented', __('cargo.not_implemented', ['provider' => 'UPS']));
    }

    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('ups_not_implemented', __('cargo.not_implemented', ['provider' => 'UPS']));
    }

    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult
    {
        return ServiceResult::fail('ups_not_implemented', __('cargo.not_implemented', ['provider' => 'UPS']));
    }

    public function track(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('ups_not_implemented', __('cargo.not_implemented', ['provider' => 'UPS']));
    }

    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        return ServiceResult::fail('ups_not_implemented', __('cargo.not_implemented', ['provider' => 'UPS']));
    }

    public function getServiceCode(): string
    {
        return 'ups';
    }

    /**
     * @return array<string, bool>
     */
    public function getCapabilities(): array
    {
        return ['webhook' => true, 'cod' => false, 'label_zpl' => true, 'label_a4' => true, 'tracking_batch' => true];
    }
}
