<?php

namespace App\Services\Cargo\Mng;

use App\Services\Cargo\Mng\Mapper\ShipmentMapper;
use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Support\ServiceResult;
use SoapFault;

class ShipmentService
{
    public function __construct(
        private readonly Client $client,
        private readonly ShipmentMapper $mapper,
    ) {}

    /**
     * @return ServiceResult<array{tracking_number: string, label_url: ?string}>
     */
    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        try {
            $params = $this->mapper->toCreateShipmentParams($request, $this->client->authParams(), $this->client->customerCode());
            $response = $this->client->call('createShipment', $params);

            $trackingNumber = $response['trackingNumber']
                ?? $response['shipmentResult']['trackingNumber']
                ?? null;

            if (! $trackingNumber) {
                return ServiceResult::fail('mng_no_tracking', __('cargo.no_tracking'));
            }

            return ServiceResult::ok(['tracking_number' => $trackingNumber, 'label_url' => null]);
        } catch (SoapFault $e) {
            return ServiceResult::fail('mng_soap_error', $e->getMessage());
        }
    }

    /**
     * @return ServiceResult<null>
     */
    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        try {
            $this->client->call('cancelShipment', [
                'auth' => $this->client->authParams(),
                'trackingNumber' => $trackingNumber,
            ]);

            return ServiceResult::ok(null);
        } catch (SoapFault $e) {
            return ServiceResult::fail('mng_cancel_error', $e->getMessage());
        }
    }

    /**
     * @return ServiceResult<string>
     */
    public function getLabel(string $trackingNumber, string $format = 'zpl'): ServiceResult
    {
        try {
            $response = $this->client->call('getLabel', [
                'auth' => $this->client->authParams(),
                'trackingNumber' => $trackingNumber,
                'labelFormat' => $format,
            ]);

            $labelData = $response['labelData'] ?? $response['labelResult']['labelData'] ?? null;

            if (! $labelData) {
                return ServiceResult::fail('mng_label_empty', __('cargo.label_empty'));
            }

            return ServiceResult::ok($labelData);
        } catch (SoapFault $e) {
            return ServiceResult::fail('mng_label_error', $e->getMessage());
        }
    }
}
