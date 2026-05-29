<?php

namespace App\Services\Cargo\Dhl;

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;
use DateTimeInterface;

/**
 * PR 4.10 — DHL (REST, e-ihracat). Scaffold; canlı entegrasyon ileri PR.
 */
class DhlService implements CargoProvider
{
    public function __construct(CargoCredential $credential)
    {
        // DHL REST entegrasyonu henüz aktif değil
    }

    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        return ServiceResult::fail('dhl_not_implemented', __('cargo.not_implemented', ['provider' => 'DHL']));
    }

    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('dhl_not_implemented', __('cargo.not_implemented', ['provider' => 'DHL']));
    }

    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult
    {
        return ServiceResult::fail('dhl_not_implemented', __('cargo.not_implemented', ['provider' => 'DHL']));
    }

    public function track(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('dhl_not_implemented', __('cargo.not_implemented', ['provider' => 'DHL']));
    }

    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        return ServiceResult::fail('dhl_not_implemented', __('cargo.not_implemented', ['provider' => 'DHL']));
    }

    public function getServiceCode(): string
    {
        return 'dhl';
    }

    /**
     * @return array<string, bool>
     */
    public function getCapabilities(): array
    {
        return ['webhook' => true, 'cod' => false, 'label_zpl' => true, 'label_a4' => true, 'tracking_batch' => true];
    }
}
