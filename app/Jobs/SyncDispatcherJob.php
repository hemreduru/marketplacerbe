<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MarketplaceListing;
use App\Models\SyncDispatchEntry;
use App\Models\UserMarketplaceCredential;
use App\Services\MarketplaceManager;
use App\Services\Marketplaces\Contracts\InventoryWriter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Tek bir SyncDispatchEntry'yi pazaryerine yansıt. Write-guard ile
 * (env MARKETPLACE_WRITE_ENABLED + credential.write_enabled) korumalı.
 * Başarısızlıkta exponential backoff ile yeniden dener; 5. denemede
 * status=failed.
 */
class SyncDispatcherJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $dispatchEntryId) {}

    public function handle(): void
    {
        /** @var SyncDispatchEntry|null $entry */
        $entry = SyncDispatchEntry::query()->find($this->dispatchEntryId);

        if ($entry === null || $entry->status !== 'pending') {
            return;
        }

        $listing = $entry->listing()->with('credential.marketplace')->first();
        $credential = $listing?->credential;

        if ($listing === null || $credential === null || ! $this->isWriteAllowed($credential)) {
            $entry->update([
                'status' => 'skipped',
                'last_error' => 'write_disabled',
                'last_attempt_at' => now(),
            ]);

            Log::info('sync dispatch skipped (write disabled or no listing)', [
                'entry_id' => $entry->id,
                'credential_id' => $credential?->id,
            ]);

            return;
        }

        $entry->increment('attempt_count');
        $entry->update(['last_attempt_at' => now()]);

        try {
            $service = app(MarketplaceManager::class)->productService($credential);

            if (! $service instanceof InventoryWriter) {
                $entry->update(['status' => 'skipped', 'last_error' => 'no_inventory_writer']);

                return;
            }

            $result = $service->updatePriceAndInventory([$this->buildItem($entry, $listing, $credential->marketplace->slug)]);

            if ($result->ok) {
                $entry->update(['status' => 'sent', 'last_error' => null]);

                return;
            }

            // API iş hatası (network değil) — backoff ile yeniden dene.
            $entry->update([
                'last_error' => trim(($result->errorCode ?? '').' '.($result->errorMessage ?? '')),
                'next_attempt_at' => now()->addSeconds($this->backoffSecondsForAttempt($entry->attempt_count)),
                'status' => $entry->attempt_count >= 5 ? 'failed' : 'pending',
            ]);
        } catch (Throwable $e) {
            $entry->update([
                'last_error' => $e->getMessage(),
                'next_attempt_at' => now()->addSeconds($this->backoffSecondsForAttempt($entry->attempt_count)),
                'status' => $entry->attempt_count >= 5 ? 'failed' : 'pending',
            ]);

            throw $e;
        }
    }

    /**
     * Kanonik payload'ı (new_stock/listed_price) pazaryeri-özel item'a çevirir;
     * null alanlar çıkarılır (stok-yalnız / fiyat-yalnız güncellemeler).
     *
     * NOT (kod-dışı, flag): Hepsiburada item anahtar isimleri canlı HB API ile
     * doğrulanmalı (procurement — HB SIT erişimi). Trendyol formatı doğru.
     *
     * @return array<string, mixed>
     */
    private function buildItem(SyncDispatchEntry $entry, MarketplaceListing $listing, string $slug): array
    {
        $payload = $entry->payload_json;
        $stock = $payload['new_stock'] ?? null;
        $price = $payload['listed_price'] ?? null;

        $item = match ($slug) {
            'hepsiburada' => [
                'hepsiburadaSku' => $listing->remote_product_id,
                'merchantSku' => $listing->remote_sku,
                'availableStock' => $stock,
                'price' => $price,
            ],
            default => [
                'barcode' => $listing->remote_barcode ?? $listing->remote_sku,
                'quantity' => $stock,
                'salePrice' => $price,
                'listPrice' => $price,
            ],
        };

        return array_filter($item, fn ($v) => $v !== null);
    }

    private function isWriteAllowed(?UserMarketplaceCredential $credential): bool
    {
        if (! (bool) config('marketplace.write_enabled', false)) {
            return false;
        }

        if ($credential === null) {
            return false;
        }

        // Per-credential write_enabled kolonu Faz 0 öncesi modelde yoktu.
        // additional_credentials JSON'da `write_enabled => true` kontrolüne fallback.
        $extra = $credential->additional_credentials ?? [];

        return (bool) ($extra['write_enabled'] ?? false);
    }

    private function backoffSecondsForAttempt(int $attempt): int
    {
        $schedule = [30, 120, 600, 3600, 21600];

        return $schedule[min($attempt, count($schedule)) - 1] ?? 21600;
    }
}
