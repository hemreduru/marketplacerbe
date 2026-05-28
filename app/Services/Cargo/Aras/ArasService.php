<?php

namespace App\Services\Cargo\Aras;

use App\Models\CargoCredential;
use App\Services\Cargo\Aras\Mapper\ShipmentMapper;
use App\Services\Cargo\Aras\Mapper\TrackingMapper;
use App\Services\Cargo\Contracts\CargoProvider;
use App\Services\Cargo\ValueObjects\LabelFormat;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;
use DateTimeInterface;

class ArasService implements CargoProvider
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
        return $this->shipmentService->getLabel($trackingNumber, $format->isThermal() ? 'zpl' : 'pdf');
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
