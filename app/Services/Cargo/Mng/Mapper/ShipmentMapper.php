<?php

namespace App\Services\Cargo\Mng\Mapper;

use App\Services\Cargo\ValueObjects\CargoAddress;
use App\Services\Cargo\ValueObjects\ShipmentRequest;

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
            'sender' => $this->mapAddress($request->sender),
            'receiver' => $this->mapAddress($request->receiver),
            'shipmentInfo' => [
                'orderReference' => $request->orderReference,
                'paymentType' => $request->paymentType->value,
                'totalWeight' => $firstPackage?->weightKg ?? 0,
                'totalDesi' => $firstPackage?->desi ?? 0,
                'totalPackages' => count($packages),
            ],
        ];
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
            'city' => $address->city,
            'district' => $address->district,
            'address' => $address->address,
            'postalCode' => $address->postalCode,
        ];
    }
}
