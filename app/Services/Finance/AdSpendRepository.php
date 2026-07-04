<?php

namespace App\Services\Finance;

use App\Models\AdMetric;

/**
 * Reklam harcaması sorguları — kâr motoruna gerçek spend verisi sağlar.
 */
class AdSpendRepository
{
    /**
     * Credential + pazaryeri + tarih aralığındaki toplam reklam harcaması.
     */
    public function totalSpend(int $credentialId, string $marketplaceCode, string $fromDate, string $toDate): string
    {
        $sum = AdMetric::query()
            ->whereBetween('date', [$fromDate, $toDate])
            ->whereHas('campaign', function ($query) use ($credentialId, $marketplaceCode) {
                $query->where('user_marketplace_credential_id', $credentialId)
                    ->where('marketplace_code', $marketplaceCode);
            })
            ->sum('spend');

        return bcround((string) $sum, 4);
    }
}
