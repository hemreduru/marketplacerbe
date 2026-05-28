<?php

namespace App\Services\Cargo\ValueObjects;

use App\Support\Enums\CargoLabelFormat;

/**
 * Kargo etiketi format seçimi.
 */
final readonly class LabelFormat
{
    public function __construct(
        public CargoLabelFormat $format = CargoLabelFormat::A4Pdf,
        public int $positionsPerPage = 6,
    ) {}

    public static function a4(int $positionsPerPage = 6): self
    {
        return new self(CargoLabelFormat::A4Pdf, $positionsPerPage);
    }

    public static function zpl(): self
    {
        return new self(CargoLabelFormat::Zpl, 1);
    }

    public static function png(): self
    {
        return new self(CargoLabelFormat::Png, 1);
    }

    public function isThermal(): bool
    {
        return $this->format === CargoLabelFormat::Zpl;
    }
}
