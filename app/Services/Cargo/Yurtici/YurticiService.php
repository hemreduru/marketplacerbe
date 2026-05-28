<?php

namespace App\Services\Cargo\Yurtici;

use App\Models\CargoCredential;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Services\Cargo\Yurtici\Mapper\ShipmentMapper;
use App\Services\Cargo\Yurtici\Mapper\TrackingMapper;
use App\Support\ServiceResult;
use DateTimeInterface;

/**
 * Yurtici Kargo CargoProvider implementasyonu.
 */
class YurticiService implements CargoProvider
{
    private Client $client;

    private ShipmentService $shipmentService;

    private TrackingService $trackingService;

    public function __construct(CargoCredential $credential)
    {
        $this->client = new Client($credential);
        $this->shipmentService = new ShipmentService($this->client, new ShipmentMapper);
        $this->trackingService = new TrackingService($this->client, new TrackingMapper);
    }

    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        return $this->shipmentService->createShipment($request);
    }

    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        return $this->shipmentService->cancelShipment($trackingNumber);
    }

    public function getLabel(string $trackingNumber, LabelFormat $format): ServiceResult
    {
        $formatCode = $format->isThermal() ? 'zpl' : 'pdf';

        return $this->shipmentService->getLabel($trackingNumber, $formatCode);
    }

    public function track(string $trackingNumber): ServiceResult
    {
        return $this->trackingService->track($trackingNumber);
    }

    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        return $this->trackingService->listStatusUpdates($since);
    }

    public function getServiceCode(): string
    {
        return 'yurtici';
    }

    public function getCapabilities(): array
    {
        return [
            'webhook' => false,
            'cod' => true,
            'label_zpl' => true,
            'label_a4' => true,
            'tracking_batch' => true,
        ];
    }
}
