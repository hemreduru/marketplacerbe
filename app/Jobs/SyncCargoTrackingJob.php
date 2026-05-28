<?php

namespace App\Jobs;

use App\Models\Shipment;
use App\Services\Cargo\CargoManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncCargoTrackingJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function handle(CargoManager $manager): void
    {
        $activeShipments = Shipment::active()->whereNotNull('tracking_number')->get();

        foreach ($activeShipments as $shipment) {
            try {
                $cargoCode = $shipment->cargoProvider->code;
                $provider = $manager->forUser($shipment->user)->provider($cargoCode);

                $result = $provider->track($shipment->tracking_number);

                if ($result->ok) {
                    $this->processTrackingResult($shipment, $result->data);
                }
            } catch (\Throwable $e) {
                Log::warning('Cargo tracking sync failed for shipment', [
                    'shipment_id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * @param  array{status: string, location: ?string, timestamp: string}  $trackingData
     */
    private function processTrackingResult(Shipment $shipment, array $trackingData): void
    {
        $newStatus = $trackingData['status'];
        $occurredAt = $trackingData['timestamp'];

        if ($newStatus !== $shipment->status) {
            $shipment->events()->firstOrCreate(
                [
                    'status' => $newStatus,
                    'source' => 'polling',
                    'external_reference' => $shipment->tracking_number,
                ],
                [
                    'occurred_at' => $occurredAt,
                    'location' => $trackingData['location'],
                    'description' => __('cargo.status_'.$newStatus),
                ],
            );

            $shipment->update(['status' => $newStatus]);

            if ($newStatus === 'delivered') {
                $shipment->update(['delivered_at' => now()]);
            } elseif ($newStatus === 'in_transit' && ! $shipment->shipped_at) {
                $shipment->update(['shipped_at' => now()]);
            }
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SyncCargoTrackingJob failed', [
            'error' => $e->getMessage(),
        ]);
    }
}
