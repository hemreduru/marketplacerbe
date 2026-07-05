<?php

namespace App\Services\Finance;

use App\Models\OrderItemFinancial;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Kalem bazlı kâr defterinin (order_item_financials) okuma katmanı.
 *
 * Raporlar ve dashboard kârı BURADAN okur — istek anında yeniden hesap yok;
 * tablo zaten tahmin/settlement karışımı en iyi bilgiyi tutar.
 */
class ProfitAggregator
{
    private const SCALE = 4;

    /**
     * SKU (master product) bazında dönem toplamı.
     *
     * @return array<string, string>
     */
    public function forSku(int $masterProductId, string $fromYmd, string $toYmd): array
    {
        return $this->aggregate(
            OrderItemFinancial::query()->where('master_product_id', $masterProductId),
            $fromYmd,
            $toYmd
        );
    }

    /**
     * Credential (mağaza) bazında dönem toplamı.
     *
     * @return array<string, string>
     */
    public function forCredential(int $credentialId, string $fromYmd, string $toYmd): array
    {
        return $this->aggregate(
            OrderItemFinancial::query()->where('user_marketplace_credential_id', $credentialId),
            $fromYmd,
            $toYmd
        );
    }

    /**
     * Kullanıcının tüm mağazaları için dönem toplamı.
     *
     * @return array<string, string>
     */
    public function forUser(User $user, string $fromYmd, string $toYmd): array
    {
        return $this->aggregate(
            OrderItemFinancial::query()->whereHas(
                'credential',
                fn ($q) => $q->where('user_id', $user->id)
            ),
            $fromYmd,
            $toYmd
        );
    }

    /**
     * SKU P&L rapor satırları: master ürün başına adet/ciro/maliyet/kâr/marj
     * + mutabakat durum sayıları.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function skuTable(User $user, string $fromYmd, string $toYmd): Collection
    {
        $rows = OrderItemFinancial::query()
            ->whereHas('credential', fn ($q) => $q->where('user_id', $user->id))
            ->whereNotNull('master_product_id')
            ->whereBetween('order_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->with('master:id,sku,title')
            ->get();

        return $rows
            ->groupBy('master_product_id')
            ->map(function (Collection $group) {
                $first = $group->first();

                $sum = fn (string $col): string => bcround(
                    $group->reduce(fn (string $carry, OrderItemFinancial $f) => bcadd($carry, (string) $f->{$col}, 6), '0'),
                    self::SCALE
                );

                $netRevenue = $sum('net_revenue');
                $netProfit = $sum('net_profit');

                /** @var array<string, mixed> $row */
                $row = [
                    'master_product_id' => $first->master_product_id,
                    'sku' => $first->master->sku ?? null,
                    'title' => $first->master->title ?? null,
                    'items' => $group->count(),
                    'net_revenue' => $netRevenue,
                    'cogs' => $sum('cogs'),
                    'commission' => $sum('commission'),
                    'shipping' => $sum('shipping'),
                    'stopaj' => $sum('stopaj'),
                    'ad_cost' => $sum('ad_cost'),
                    'return_cost' => $sum('return_cost'),
                    'net_profit' => $netProfit,
                    'margin' => bccomp($netRevenue, '0', 6) === 0
                        ? '0.00'
                        : bcround(bcmul(bcdiv($netProfit, $netRevenue, 6), '100', 6), 2),
                    'status_mix' => $group->countBy(fn (OrderItemFinancial $f) => $f->reconciliation_status->value)->all(),
                ];

                return $row;
            })
            ->sortByDesc(fn (array $row) => (float) $row['net_profit'])
            ->values();
    }

    /**
     * @param  Builder<OrderItemFinancial>  $query
     * @return array<string, string>
     */
    protected function aggregate($query, string $fromYmd, string $toYmd): array
    {
        $row = $query
            ->whereBetween('order_date', [$fromYmd.' 00:00:00', $toYmd.' 23:59:59'])
            ->select([
                DB::raw('COUNT(*) as item_count'),
                DB::raw('SUM(net_revenue) as net_revenue'),
                DB::raw('SUM(cogs) as cogs'),
                DB::raw('SUM(commission) as commission'),
                DB::raw('SUM(service_fee) as service_fee'),
                DB::raw('SUM(shipping) as shipping'),
                DB::raw('SUM(stopaj) as stopaj'),
                DB::raw('SUM(ad_cost) as ad_cost'),
                DB::raw('SUM(return_cost) as return_cost'),
                DB::raw('SUM(net_profit) as net_profit'),
            ])
            ->first();

        $netRevenue = bcround((string) ($row->net_revenue ?? '0'), self::SCALE);
        $netProfit = bcround((string) ($row->net_profit ?? '0'), self::SCALE);

        return [
            'item_count' => (string) ($row->item_count ?? 0),
            'net_revenue' => $netRevenue,
            'cogs' => bcround((string) ($row->cogs ?? '0'), self::SCALE),
            'commission' => bcround((string) ($row->commission ?? '0'), self::SCALE),
            'service_fee' => bcround((string) ($row->service_fee ?? '0'), self::SCALE),
            'shipping' => bcround((string) ($row->shipping ?? '0'), self::SCALE),
            'stopaj' => bcround((string) ($row->stopaj ?? '0'), self::SCALE),
            'ad_cost' => bcround((string) ($row->ad_cost ?? '0'), self::SCALE),
            'return_cost' => bcround((string) ($row->return_cost ?? '0'), self::SCALE),
            'net_profit' => $netProfit,
            'margin' => bccomp($netRevenue, '0', 6) === 0
                ? '0.00'
                : bcround(bcmul(bcdiv($netProfit, $netRevenue, 6), '100', 6), 2),
        ];
    }
}
