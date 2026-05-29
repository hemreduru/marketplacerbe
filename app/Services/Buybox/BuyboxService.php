<?php

namespace App\Services\Buybox;

use App\Models\BuyboxSnapshot;
use App\Models\MarketplaceListing;
use App\Models\User;
use App\Models\UserMarketplaceCredential;
use App\Support\ServiceResult;
use Illuminate\Support\Collection;

/**
 * PR 4.8 — Buybox / rakip takip servisi (Spec 11.1 buybox_loss).
 *
 * Canlı buybox sorgusu Trendyol API erişimi gerektirir (ileri PR); bu servis
 * snapshot kaydı, son durum okuma ve buybox kaybı tespiti mantığını içerir.
 */
class BuyboxService
{
    /**
     * @param  array{has_buybox: bool, our_price: float|string, competitor_price?: float|string|null, competitor_seller?: ?string, checked_at?: mixed}  $data
     */
    public function record(MarketplaceListing $listing, array $data): BuyboxSnapshot
    {
        return BuyboxSnapshot::create([
            'marketplace_listing_id' => $listing->id,
            'has_buybox' => $data['has_buybox'],
            'our_price' => $data['our_price'],
            'competitor_price' => $data['competitor_price'] ?? null,
            'competitor_seller' => $data['competitor_seller'] ?? null,
            'checked_at' => $data['checked_at'] ?? now(),
        ]);
    }

    /**
     * Kullanıcının her listing'i için son buybox durumu.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function trackerRows(User $user): Collection
    {
        $listings = MarketplaceListing::whereHas('credential', fn ($q) => $q->where('user_id', $user->id))
            ->with('master')
            ->get();

        if ($listings->isEmpty()) {
            return collect();
        }

        $latest = BuyboxSnapshot::whereIn('marketplace_listing_id', $listings->pluck('id'))
            ->orderByDesc('checked_at')
            ->get()
            ->unique('marketplace_listing_id')
            ->keyBy('marketplace_listing_id');

        return $listings->map(function (MarketplaceListing $listing) use ($latest) {
            $snap = $latest->get($listing->id);

            return [
                'sku' => $listing->master?->sku ?? $listing->remote_sku,
                'title' => $listing->master?->title,
                'has_buybox' => $snap?->has_buybox,
                'our_price' => $snap?->our_price,
                'competitor_price' => $snap?->competitor_price,
                'competitor_seller' => $snap?->competitor_seller,
                'checked_at' => $snap?->checked_at,
            ];
        })->filter(fn ($r) => $r['has_buybox'] !== null)->values();
    }

    /**
     * Buybox kaybedilen listing'ler (son snapshot has_buybox=false).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function lostBuybox(User $user): Collection
    {
        return $this->trackerRows($user)->filter(fn ($r) => $r['has_buybox'] === false)->values();
    }

    /**
     * Canlı buybox verisi çek (scaffold — Trendyol buybox API config gerektirir).
     *
     * @return ServiceResult<array<string, int>>
     */
    public function sync(UserMarketplaceCredential $credential): ServiceResult
    {
        $endpoint = config("marketplaces.{$credential->marketplace?->slug}.buybox.endpoint");

        if (empty($endpoint)) {
            return ServiceResult::fail('buybox_endpoint_not_configured', __('reports.buybox_not_configured'));
        }

        return ServiceResult::ok(['checked' => 0]);
    }
}
