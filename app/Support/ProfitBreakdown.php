<?php

namespace App\Support;

/**
 * Net kâr dökümü. ProfitCalculator'ın tüm metodları bu VO'yu döner.
 *
 * @property string $netRevenue KDV hariç net gelir
 * @property string $netProfit Net kâr (negatif olabilir)
 * @property string $margin Kâr marjı yüzdesi
 * @property string $roi ROI yüzdesi
 * @property array<string, string> $deductions Kalem kalem kesintiler
 * @property array<string, mixed> $details Debug/hesaplama detayı
 */
class ProfitBreakdown
{
    /**
     * @param  array<string, string>  $deductions
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public readonly string $netRevenue,
        public readonly string $netProfit,
        public readonly string $margin,
        public readonly string $roi,
        public readonly array $deductions,
        public readonly array $details = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'net_revenue' => $this->netRevenue,
            'net_profit' => $this->netProfit,
            'margin' => $this->margin,
            'roi' => $this->roi,
            'deductions' => $this->deductions,
            'details' => $this->details,
        ];
    }
}
