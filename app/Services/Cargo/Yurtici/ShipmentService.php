<?php

namespace App\Services\Cargo\Yurtici;

use App\Services\Cargo\ValueObjects\ShipmentRequest;
use App\Services\Cargo\Yurtici\Mapper\ShipmentMapper;
use App\Support\ServiceResult;
use SoapFault;

/**
 * Yurtici Kargo gönderi işlemleri.
 */
class ShipmentService
{
    public function __construct(
        private readonly Client $client,
        private readonly ShipmentMapper $mapper,
    ) {}

    /**
     * Yeni kargo gönderisi oluşturur, tracking number döner.
     *
     * @return ServiceResult<array{tracking_number: string, label_url: ?string}>
     */
    public function createShipment(ShipmentRequest $request): ServiceResult
    {
        try {
            $params = $this->mapper->toCreateShipmentParams($request, $this->client->authParams(), $this->client->customerCode());
            $response = $this->client->callShipment('createShipment', $params);

            $trackingNumber = $this->mapper->extractTrackingNumber($response);

            if (! $trackingNumber) {
                return ServiceResult::fail(
                    'yurtici_no_tracking',
                    __('cargo.no_tracking'),
                    $response,
                );
            }

            return ServiceResult::ok([
                'tracking_number' => $trackingNumber,
                'label_url' => null,
            ]);
        } catch (SoapFault $e) {
            return ServiceResult::fail('yurtici_soap_error', $e->getMessage());
        }
    }

    /**
     * Gönderiyi iptal eder.
     *
     * @return ServiceResult<null>
     */
    public function cancelShipment(string $trackingNumber): ServiceResult
    {
        try {
            $params = [
                'auth' => $this->client->authParams(),
                'trackingNumber' => $trackingNumber,
            ];

            $this->client->callShipment('cancelShipment', $params);

            return ServiceResult::ok(null);
        } catch (SoapFault $e) {
            return ServiceResult::fail('yurtici_cancel_error', $e->getMessage());
        }
    }

    /**
     * Etiket dosyasını döner (ZPL string veya PDF base64).
     *
     * @return ServiceResult<string>
     */
    public function getLabel(string $trackingNumber, string $format = 'zpl'): ServiceResult
    {
        try {
            $params = [
                'auth' => $this->client->authParams(),
                'trackingNumber' => $trackingNumber,
                'labelFormat' => $format,
            ];

            $response = $this->client->callShipment('getLabel', $params);
            $labelData = $this->mapper->extractLabelData($response);

            if (! $labelData) {
                return ServiceResult::fail('yurtici_label_empty', __('cargo.label_empty'));
            }

            return ServiceResult::ok($labelData);
        } catch (SoapFault $e) {
            return ServiceResult::fail('yurtici_label_error', $e->getMessage());
        }
    }
}
