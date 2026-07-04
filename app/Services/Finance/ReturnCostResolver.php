<?php

namespace App\Services\Finance;

use App\Models\Claim;
use Illuminate\Support\Collection;

/**
 * Bir siparişin GERÇEK iade maliyetini çözer.
 *
 * İade basit bir ters kayıt değildir: komisyon satıcıya iade edilir AMA
 * iade-yönü kargo + ceza satıcının üzerinde kalır. Bu sınıf onaylanmış
 * Claim kayıtlarından sipariş bazında net iade maliyetini üretir;
 * SettlementReconciler bunu kalemlere dağıtır.
 */
class ReturnCostResolver
{
    /**
     * Credential + tarih aralığındaki onaylı iadelerin sipariş bazlı haritası.
     *
     * @return Collection<int|string, numeric-string> order_number => net iade maliyeti (bcmath)
     */
    public function approvedReturnCostMap(int $credentialId, string $fromYmd, string $toYmd): Collection
    {
        return Claim::query()
            ->where('user_marketplace_credential_id', $credentialId)
            ->whereNotNull('approved_at')
            ->whereNotNull('order_number')
            ->whereBetween('claim_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->get()
            ->groupBy('order_number')
            ->map(function (Collection $claims): string {
                $total = '0.0000';
                foreach ($claims as $claim) {
                    // Şimdilik net maliyet = refund_amount; iade kargo + ceza
                    // kalemleri settlement deduction'larından geldiğinde
                    // (kargo faturası satırları) oraya yansır.
                    $total = bcadd($total, (string) ($claim->refund_amount ?? '0'), 4);
                }

                return $total;
            });
    }
}
