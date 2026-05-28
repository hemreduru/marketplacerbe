<?php

namespace App\Services\Inventory;

use App\Models\MasterProduct;
use App\Models\StockEvent;
use App\Support\ServiceResult;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Stok olayını append-only ledger'a yaz, master_products.current_stock'u
 * atomik UPDATE + optimistic lock ile yansıt.
 *
 * Bu sınıf event'leri SIRAYLA tek transaction içinde işler. Race condition
 * önlemi {@see MasterProduct::version} kolonu üzerinden yapılır.
 */
class MasterProductStockProjector
{
    /**
     * @param  array{
     *   master_product_id: int,
     *   event_type: string,
     *   source: string,
     *   source_reference: string|null,
     *   quantity_delta: int,
     *   occurred_at: CarbonInterface|string,
     *   marketplace_listing_id?: int|null,
     * }  $payload
     * @return ServiceResult<StockEvent>
     */
    public function record(array $payload): ServiceResult
    {
        $payload['event_uuid'] ??= (string) Str::uuid();
        $payload['marketplace_listing_id'] ??= null;

        try {
            return DB::transaction(function () use ($payload) {
                /** @var StockEvent $event */
                $event = StockEvent::create($payload + ['processed_at' => now()]);

                $this->applyToMaster(
                    (int) $payload['master_product_id'],
                    (int) $payload['quantity_delta']
                );

                return ServiceResult::ok($event);
            });
        } catch (QueryException $e) {
            // İdempotency violation (unique constraint) → sessiz skip
            if ($this->isUniqueViolation($e)) {
                return ServiceResult::fail(
                    'duplicate_event',
                    'Bu olay daha önce işlenmiş (source + source_reference + event_type).',
                );
            }
            throw $e;
        }
    }

    /**
     * Optimistic lock ile master kaydını güncelle. Aynı satıra eşzamanlı
     * UPDATE'lerde sadece version eşleşeni başarılı olur; diğeri retry
     * eder.
     */
    private function applyToMaster(int $masterId, int $delta): void
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
                    'current_stock' => DB::raw('current_stock + ('.(int) $delta.')'),
                    'version' => DB::raw('version + 1'),
                ]);

            if ($affected === 1) {
                return;
            }

            if ($attempt >= $maxAttempts) {
                Log::warning('stock projector retry exhausted', [
                    'master_product_id' => $masterId,
                    'delta' => $delta,
                    'attempts' => $attempt,
                ]);

                throw new \RuntimeException("Stock projector retry exhausted for master {$masterId}");
            }

            usleep(10_000); // 10ms
        }
    }

    private function isUniqueViolation(Throwable $e): bool
    {
        // MySQL: 1062 / 23000; SQLite: 19 / 23000
        $sqlState = $e->getCode();
        $driverCode = $e->errorInfo[1] ?? null;

        if ($sqlState === '23000') {
            return true;
        }

        return in_array($driverCode, [1062, 19], true);
    }
}
