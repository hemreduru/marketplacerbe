<?php

namespace App\Services\Cargo\ValueObjects;

/**
 * Adres bilgisi (gönderici veya alici).
 */
final readonly class CargoAddress
{
    public function __construct(
        public string $fullName,
        public string $phone,
        public string $city,
        public string $district,
        public string $address,
        public ?string $companyName = null,
        public ?string $email = null,
        public ?string $postalCode = null,
        public ?string $taxNumber = null,
    ) {}
}
