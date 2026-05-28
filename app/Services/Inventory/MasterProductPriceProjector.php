<?php

namespace App\Services\Inventory;

use App\Models\MasterProduct;
use App\Models\PriceEvent;
use App\Support\ServiceResult;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
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

                $this->applyToMaster(
                    (int) $payload['master_product_id'],
                    $payload['new_price'],
                );

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

    private function applyToMaster(int $masterId, mixed $newPrice): void
    {
        $maxAttempts = 5;
        $attempt = 0;

        while (true) {
            $attempt++;

            $master = MasterProduct::query()
                ->whereKey($masterId)
                ->firstOrFail();

            $affected = MasterProduct::query()
                ->whereKey($masterId)
                ->where('version', $master->version)
                ->update([
                    'current_price' => $newPrice,
                    'version' => DB::raw('version + 1'),
                ]);

            if ($affected === 1) {
                return;
            }

            if ($attempt >= $maxAttempts) {
                Log::warning('price projector retry exhausted', [
                    'master_product_id' => $masterId,
                    'new_price' => $newPrice,
                    'attempts' => $attempt,
                ]);

                throw new \RuntimeException("Price projector retry exhausted for master {$masterId}");
            }

            usleep(10_000);
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
