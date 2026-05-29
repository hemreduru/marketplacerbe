<?php

namespace App\Services\Ads;

use App\Models\AdCampaign;
use App\Models\User;
use App\Services\Reports\ReportPeriod;
use Illuminate\Support\Collection;

/**
 * PR 4.6 — Reklam performans raporu veri katmanı (Spec 10.8, 9.13).
 *
 * Saklanan ad_campaigns + ad_metrics üzerinden ROAS/ACoS/katkı hesaplar.
 */
class AdReportService
{
    /**
     * @return array{campaigns: Collection<int, array<string, mixed>>, totals: array<string, mixed>}
     */
    public function report(User $user, ReportPeriod $period): array
    {
        $credentialIds = $user->marketplaceCredentials()->pluck('id')->all();

        $campaigns = AdCampaign::whereIn('user_marketplace_credential_id', $credentialIds)
            ->withSum(['metrics as spend' => fn ($q) => $q->whereBetween('date', [$period->from, $period->to])], 'spend')
            ->withSum(['metrics as revenue' => fn ($q) => $q->whereBetween('date', [$period->from, $period->to])], 'attributed_revenue')
            ->get();

        $rows = $campaigns->map(function (AdCampaign $c) {
            $spend = (string) ($c->spend ?? '0');
            $revenue = (string) ($c->revenue ?? '0');

            return [
                'name' => $c->name,
                'marketplace_code' => $c->marketplace_code,
                'spend' => $spend,
                'revenue' => $revenue,
                'roas' => $this->roas($spend, $revenue),
                'acos' => $this->acos($spend, $revenue),
                'contribution' => bcsub($revenue, $spend, 4),
                'profitable' => bccomp($revenue, $spend, 4) >= 0,
            ];
        })->sortByDesc('spend')->values();

        $totalSpend = (string) $rows->sum(fn ($r) => (float) $r['spend']);
        $totalRevenue = (string) $rows->sum(fn ($r) => (float) $r['revenue']);

        return [
            'campaigns' => $rows,
            'totals' => [
                'spend' => $totalSpend,
                'revenue' => $totalRevenue,
                'roas' => $this->roas($totalSpend, $totalRevenue),
                'acos' => $this->acos($totalSpend, $totalRevenue),
                'contribution' => bcsub($totalRevenue, $totalSpend, 4),
            ],
        ];
    }

    public function roas(string $spend, string $revenue): float
    {
        return bccomp($spend, '0', 4) === 0 ? 0.0 : round((float) bcdiv($revenue, $spend, 4), 2);
    }

    public function acos(string $spend, string $revenue): float
    {
        return bccomp($revenue, '0', 4) === 0 ? 0.0 : round((float) bcmul(bcdiv($spend, $revenue, 4), '100', 4), 2);
    }
}
