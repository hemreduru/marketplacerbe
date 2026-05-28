<?php

namespace App\Services\Cargo\Surat;

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;
use DateTimeInterface;

class SuratService implements CargoProvider
{
    public function __construct(CargoCredential $credential)
    {
        // Sürat Kargo entegrasyonu henüz aktif değil
    }

    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        return ServiceResult::fail('surat_not_implemented', __('cargo.not_implemented', ['provider' => 'Sürat']));
    }

    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('surat_not_implemented', __('cargo.not_implemented', ['provider' => 'Sürat']));
    }

    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult
    {
        return ServiceResult::fail('surat_not_implemented', __('cargo.not_implemented', ['provider' => 'Sürat']));
    }

    public function track(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('surat_not_implemented', __('cargo.not_implemented', ['provider' => 'Sürat']));
    }

    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        return ServiceResult::fail('surat_not_implemented', __('cargo.not_implemented', ['provider' => 'Sürat']));
    }

    public function getServiceCode(): string
    {
        return 'surat';
    }

    public function getCapabilities(): array
    {
        return ['webhook' => false, 'cod' => true, 'label_zpl' => false, 'label_a4' => true, 'tracking_batch' => false];
    }
}
