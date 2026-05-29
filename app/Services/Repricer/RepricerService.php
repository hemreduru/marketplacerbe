<?php

namespace App\Services\Repricer;

use App\Models\BuyboxSnapshot;
use App\Models\MasterProduct;
use App\Models\PriceEvent;
use App\Models\RepricerRule;
use App\Models\SyncDispatchEntry;
use App\Models\User;
use App\Support\Enums\PriceEventType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * PR 4.9 — Kural tabanlı repricer (ML değil).
 *
 * Strateji: target_margin (maliyet üzeri markup), undercut (rakip - x), fixed (min/max clamp).
 * Trendyol 15dk cooldown'a saygılıdır: aynı master için son 15dk içinde price_event
 * varsa fiyat değişimi atılmaz. Çıktı price_events + sync_dispatch_queue (write guard'lı).
 */
class RepricerService
{
    private const COOLDOWN_MINUTES = 15;

    /**
     * @return array{evaluated: int, dispatched: int}
     */
    public function run(User $user): array
    {
        $rules = RepricerRule::where('user_id', $user->id)->where('is_active', true)->get();
        $evaluated = 0;
        $dispatched = 0;

        foreach ($rules as $rule) {
            foreach ($this->targetMasters($user, $rule) as $master) {
                $evaluated++;

                $newPrice = $this->computeNewPrice($rule, $master);
                if ($newPrice === null) {
                    continue;
                }

                $newPrice = $this->clamp($newPrice, $rule);
                if (bccomp($newPrice, (string) $master->current_price, 4) === 0) {
                    continue;
                }
                if ($this->inCooldown($master)) {
                    continue;
                }

                $this->applyPriceChange($master, $newPrice, $rule);
                $dispatched++;
            }

            $rule->update(['last_run_at' => now()]);
        }

        return ['evaluated' => $evaluated, 'dispatched' => $dispatched];
    }

    /**
     * @return Collection<int, MasterProduct>
     */
    private function targetMasters(User $user, RepricerRule $rule): Collection
    {
        if ($rule->master_product_id !== null) {
            return MasterProduct::where('id', $rule->master_product_id)
                ->where('user_id', $user->id)
                ->get();
        }

        return MasterProduct::where('user_id', $user->id)->get();
    }

    private function computeNewPrice(RepricerRule $rule, MasterProduct $master): ?string
    {
        return match ($rule->strategy) {
            'target_margin' => $this->targetMarginPrice($rule, $master),
            'undercut' => $this->undercutPrice($rule, $master),
            'fixed' => (string) $master->current_price,
            default => null,
        };
    }

    private function targetMarginPrice(RepricerRule $rule, MasterProduct $master): ?string
    {
        if ($rule->target_margin === null || bccomp((string) $master->cost_price, '0', 4) === 0) {
            return null;
        }

        // Basit maliyet-üzeri markup: yeni_fiyat = maliyet × (1 + marj/100)
        $factor = bcadd('1', bcdiv((string) $rule->target_margin, '100', 6), 6);

        return bcmul((string) $master->cost_price, $factor, 4);
    }

    private function undercutPrice(RepricerRule $rule, MasterProduct $master): ?string
    {
        $listingIds = $master->listings()->pluck('id');
        if ($listingIds->isEmpty()) {
            return null;
        }

        $snapshot = BuyboxSnapshot::whereIn('marketplace_listing_id', $listingIds)
            ->whereNotNull('competitor_price')
            ->orderByDesc('checked_at')
            ->first();

        if ($snapshot === null) {
            return null;
        }

        return bcsub((string) $snapshot->competitor_price, (string) ($rule->undercut_amount ?? '0'), 4);
    }

    private function clamp(string $price, RepricerRule $rule): string
    {
        if ($rule->min_price !== null && bccomp($price, (string) $rule->min_price, 4) < 0) {
            $price = (string) $rule->min_price;
        }
        if ($rule->max_price !== null && bccomp($price, (string) $rule->max_price, 4) > 0) {
            $price = (string) $rule->max_price;
        }

        return $price;
    }

    private function inCooldown(MasterProduct $master): bool
    {
        return PriceEvent::where('master_product_id', $master->id)
            ->where('occurred_at', '>=', Carbon::now()->subMinutes(self::COOLDOWN_MINUTES))
            ->exists();
    }

    private function applyPriceChange(MasterProduct $master, string $newPrice, RepricerRule $rule): void
    {
        DB::transaction(function () use ($master, $newPrice, $rule) {
            PriceEvent::create([
                'event_uuid' => (string) Str::uuid(),
                'master_product_id' => $master->id,
                'marketplace_listing_id' => null,
                'event_type' => PriceEventType::StrategyRecompute->value,
                'source' => 'system',
                'source_reference' => 'repricer:'.$rule->id.':'.now()->timestamp,
                'new_price' => $newPrice,
                'previous_price' => $master->current_price,
                'occurred_at' => now(),
            ]);

            $master->listings()->each(function ($listing) use ($master, $newPrice) {
                SyncDispatchEntry::create([
                    'master_product_id' => $master->id,
                    'marketplace_listing_id' => $listing->id,
                    'mutation_type' => 'price',
                    'payload_json' => ['listed_price' => $newPrice],
                    'status' => 'pending',
                ]);
            });
        });
    }
}
