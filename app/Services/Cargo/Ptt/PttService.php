<?php

namespace App\Services\Cargo\Ptt;

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;
use DateTimeInterface;

class PttService implements CargoProvider
{
    public function __construct(CargoCredential $credential)
    {
        // PTT Kargo entegrasyonu henüz aktif değil
    }

    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        return ServiceResult::fail('ptt_not_implemented', __('cargo.not_implemented', ['provider' => 'PTT']));
    }

    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('ptt_not_implemented', __('cargo.not_implemented', ['provider' => 'PTT']));
    }

    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult
    {
        return ServiceResult::fail('ptt_not_implemented', __('cargo.not_implemented', ['provider' => 'PTT']));
    }

    public function track(string $trackingNumber): ServiceResult
    {
        return ServiceResult::fail('ptt_not_implemented', __('cargo.not_implemented', ['provider' => 'PTT']));
    }

    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        return ServiceResult::fail('ptt_not_implemented', __('cargo.not_implemented', ['provider' => 'PTT']));
    }

    public function getServiceCode(): string
    {
        return 'ptt';
    }

    public function getCapabilities(): array
    {
        return ['webhook' => false, 'cod' => true, 'label_zpl' => false, 'label_a4' => true, 'tracking_batch' => false];
    }
}
