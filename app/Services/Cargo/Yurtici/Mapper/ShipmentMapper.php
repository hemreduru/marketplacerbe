<?php

namespace App\Services\Cargo\Yurtici\Mapper;

use App\Services\Cargo\ValueObjects\CargoAddress;
use App\Services\Cargo\ValueObjects\ShipmentRequest;

/**
 * Cirotik ShipmentRequest -> Yurtici SOAP parametrelerine dönüşüm.
 */
class ShipmentMapper
{
    /**
     * @param  array<string, string>  $auth
     * @return array<string, mixed>
     */
    public function toCreateShipmentParams(ShipmentRequest $request, array $auth, ?string $customerCode): array
    {
        $packages = $request->packages;
        $firstPackage = $packages[0] ?? null;

        return [
            'auth' => $auth,
            'customerCode' => $customerCode,
            'senderAddress' => $this->mapAddress($request->sender),
            'receiverAddress' => $this->mapAddress($request->receiver),
            'shipmentInfo' => [
                'orderReference' => $request->orderReference,
                'paymentType' => $request->paymentType->value,
                'totalWeight' => $firstPackage?->weightKg ?? 0,
                'totalDesi' => $firstPackage?->desi ?? 0,
                'totalPackages' => count($packages),
                'content' => $firstPackage?->content ?? '',
            ],
            'note' => $request->note,
        ];
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function extractTrackingNumber(array $response): ?string
    {
        return $response['trackingNumber']
            ?? $response['shipmentResult']['trackingNumber']
            ?? $response['result']['trackingNumber']
            ?? null;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    public function extractLabelData(array $response): ?string
    {
        return $response['labelData']
            ?? $response['labelResult']['labelData']
            ?? $response['result']['label']
            ?? null;
    }

    /**
     * @return array<string, ?string>
     */
    private function mapAddress(CargoAddress $address): array
    {
        return [
            'fullName' => $address->fullName,
            'company' => $address->companyName,
            'phone' => $address->phone,
            'email' => $address->email,
            'city' => $address->city,
            'district' => $address->district,
            'address' => $address->address,
            'postalCode' => $address->postalCode,
            'taxNumber' => $address->taxNumber,
        ];
    }
}
