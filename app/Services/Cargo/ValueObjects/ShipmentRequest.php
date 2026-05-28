<?php

namespace App\Services\Cargo\ValueObjects;

use App\Support\Enums\CargoPaymentType;

/**
 * Kargo gönderi isteği: bir sipariş için kargo firmasına gönderilecek veri.
 *
 * @param  PackageInfo[]  $packages
 */
final readonly class ShipmentRequest
{
    public function __construct(
        public CargoAddress $sender,
        public CargoAddress $receiver,
        public array $packages,
        public CargoPaymentType $paymentType = CargoPaymentType::SenderPays,
        public ?string $orderReference = null,
        public ?string $note = null,
    ) {}
}
