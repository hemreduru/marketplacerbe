<?php

namespace App\Jobs;

use App\Jobs\Concerns\HasRetryPolicy;
use App\Models\MarketplaceEvent;
use App\Models\MarketplaceListing;
use App\Services\Inventory\MasterProductStockProjector;
use App\Support\Enums\StockEventSource;
use App\Support\Enums\StockEventType;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Gelen webhook event'ini işle: stok kaydı oluştur, MasterProductStockProjector
 * ile projection'ı güncelle, gerekiyorsa sync_dispatch_queue'na yaz.
 */
class ProcessIncomingWebhookJob implements ShouldQueue
{
    use Dispatchable, HasRetryPolicy, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public readonly int $eventId) {}

    public function handle(): void
    {
        /** @var MarketplaceEvent|null $event */
        $event = MarketplaceEvent::query()->find($this->eventId);

        if ($event === null || $event->status !== 'received') {
            return;
        }

        $event->update(['status' => 'processing']);

        try {
            match ($event->event_type) {
                'order_status_changed' => $this->handleOrderStatusChanged($event),
                'claim_status_changed', 'return_status_changed' => $this->handleReturnStatusChanged($event),
                default => Log::info('Webhook: bilinmeyen event tipi', ['event_id' => $event->id]),
            };

            $event->update(['status' => 'processed', 'processed_at' => now()]);
        } catch (\Exception $e) {
            Log::error('Webhook işleme hatası: '.$e->getMessage(), [
                'event_id' => $event->id,
                'event_type' => $event->event_type,
            ]);

            $event->update([
                'status' => 'failed',
                'processing_error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    protected function handleOrderStatusChanged(MarketplaceEvent $event): void
    {
        $payload = $event->raw_payload;
        $orderNumber = (string) ($payload['orderNumber'] ?? '');
        $status = $payload['packageStatus'] ?? $payload['status'] ?? '';

        if ($orderNumber === '' || $status !== 'Created') {
            return;
        }

        $lines = $payload['lines'] ?? [];
        if (empty($lines)) {
            return;
        }

        $projector = new MasterProductStockProjector;

        foreach ($lines as $line) {
            $merchantSku = $line['merchantSku'] ?? null;
            $quantity = (int) ($line['quantity'] ?? 1);
            $barcode = $line['barcode'] ?? null;

            if ($merchantSku === null && $barcode === null) {
                continue;
            }

            $listing = MarketplaceListing::query()
                ->where(function ($q) use ($merchantSku, $barcode) {
                    if ($merchantSku) {
                        $q->orWhere('remote_sku', $merchantSku);
                    }
                    if ($barcode) {
                        $q->orWhere('remote_barcode', $barcode);
                    }
                })
                ->whereHas('credential', fn ($q) => $q->where('id', $event->user_marketplace_credential_id))
                ->first();

            $masterId = $listing?->master_product_id;
            if ($masterId === null) {
                Log::info('Webhook: listing/master bulunamadı', [
                    'merchant_sku' => $merchantSku,
                    'barcode' => $barcode,
                ]);

                continue;
            }

            $sourceRef = $orderNumber.'-'.$merchantSku;

            $projector->record([
                'master_product_id' => $masterId,
                'event_type' => StockEventType::Sale->value,
                'source' => StockEventSource::Trendyol->value,
                'source_reference' => $sourceRef,
                'quantity_delta' => -$quantity,
                'occurred_at' => Carbon::createFromTimestampMs(
                    $payload['orderDate'] ?? now()->getTimestampMs()
                ),
                'marketplace_listing_id' => $listing?->id,
            ]);
        }
    }

    protected function handleReturnStatusChanged(MarketplaceEvent $event): void
    {
        $payload = $event->raw_payload;
        $claimId = (string) ($payload['claimId'] ?? '');
        $items = $payload['items'] ?? [];

        if ($claimId === '' || empty($items)) {
            return;
        }

        $projector = new MasterProductStockProjector;

        foreach ($items as $item) {
            $barcode = $item['barcode'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 1);

            if ($barcode === null) {
                continue;
            }

            $listing = MarketplaceListing::query()
                ->where('remote_barcode', $barcode)
                ->whereHas('credential', fn ($q) => $q->where('id', $event->user_marketplace_credential_id))
                ->first();

            $masterId = $listing?->master_product_id;
            if ($masterId === null) {
                continue;
            }

            $sourceRef = $claimId.'-return-'.$barcode;

            $projector->record([
                'master_product_id' => $masterId,
                'event_type' => StockEventType::Return->value,
                'source' => StockEventSource::Trendyol->value,
                'source_reference' => $sourceRef,
                'quantity_delta' => $quantity,
                'occurred_at' => now(),
                'marketplace_listing_id' => $listing?->id,
            ]);
        }
    }
}
