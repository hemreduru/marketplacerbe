<?php

namespace App\Services\Cargo\ValueObjects;

/**
 * Kargo gönderisi için paket bilgisi.
 */
final readonly class PackageInfo
{
    public function __construct(
        public float $weightKg,
        public float $desi,
        public ?float $widthCm = null,
        public ?float $heightCm = null,
        public ?float $lengthCm = null,
        public string $content = '',
        public int $quantity = 1,
    ) {}
}
