<?php

namespace App\Services\Inventory;

use App\Models\MasterProduct;
use App\Models\PriceEvent;
use App\Support\ServiceResult;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fiyat olayını append-only ledger'a yaz, master_products.current_price'ı
 * yansıt. Trendyol 15dk fiyat değişim limitine saygı duy: aynı SKU için
 * 15dk içinde 2. event reddedilir (`rate_limited_window`).
 */
class MasterProductPriceProjector
{
    public const MIN_INTERVAL_SECONDS = 15 * 60;

    /**
     * @param  array{
     *   master_product_id: int,
     *   event_type: string,
     *   source: string,
     *   source_reference: string|null,
     *   new_price: int|float|string,
     *   previous_price?: int|float|string|null,
     *   occurred_at: CarbonInterface|string,
     *   marketplace_listing_id?: int|null,
     * }  $payload
     * @return ServiceResult<PriceEvent>
     */
    public function record(array $payload): ServiceResult
    {
        $payload['event_uuid'] ??= (string) Str::uuid();
        $payload['marketplace_listing_id'] ??= null;
        $payload['previous_price'] ??= null;

        try {
            return DB::transaction(function () use ($payload) {
                /** @var PriceEvent|null $last */
                $last = PriceEvent::query()
                    ->where('master_product_id', $payload['master_product_id'])
                    ->orderByDesc('occurred_at')
                    ->lockForUpdate()
                    ->first();

                if ($last !== null
                    && abs(now()->diffInSeconds($last->occurred_at)) < self::MIN_INTERVAL_SECONDS) {
                    return ServiceResult::fail(
                        'rate_limited_window',
                        'Aynı SKU için son fiyat değişiminden bu yana 15 dakika geçmedi.',
                    );
                }

                /** @var PriceEvent $event */
                $event = PriceEvent::create($payload + ['processed_at' => now()]);

                MasterProduct::query()
                    ->whereKey($payload['master_product_id'])
                    ->update([
                        'current_price' => $payload['new_price'],
                        'version' => DB::raw('version + 1'),
                    ]);

                return ServiceResult::ok($event);
            });
        } catch (QueryException $e) {
            if ($this->isUniqueViolation($e)) {
                return ServiceResult::fail(
                    'duplicate_event',
                    'Bu fiyat olayı daha önce işlenmiş.',
                );
            }
            throw $e;
        }
    }

    private function isUniqueViolation(Throwable $e): bool
    {
        $sqlState = $e->getCode();
        $driverCode = $e->errorInfo[1] ?? null;

        if ($sqlState === '23000') {
            return true;
        }

        return in_array($driverCode, [1062, 19], true);
    }
}
