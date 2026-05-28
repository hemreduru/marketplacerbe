<?php

namespace App\Services\Cargo\Yurtici;

use App\Services\Cargo\Yurtici\Mapper\TrackingMapper;
use App\Support\ServiceResult;
use DateTimeInterface;
use SoapFault;

/**
 * Yurtici Kargo takip işlemleri.
 */
class TrackingService
{
    public function __construct(
        private readonly Client $client,
        private readonly TrackingMapper $mapper,
    ) {}

    /**
     * Tekil takip sorgusu.
     *
     * @return ServiceResult<array{status: string, location: ?string, timestamp: string}>
     */
    public function track(string $trackingNumber): ServiceResult
    {
        try {
            $params = [
                'auth' => $this->client->authParams(),
                'trackingNumber' => $trackingNumber,
            ];

            $response = $this->client->callTracking('queryTracking', $params);

            return ServiceResult::ok($this->mapper->toTrackingResult($response));
        } catch (SoapFault $e) {
            return ServiceResult::fail('yurtici_track_error', $e->getMessage());
        }
    }

    /**
     * Belirtilen tarihten beri güncellenen gönderilerin durum listesi.
     *
     * @return ServiceResult<array<int, array{tracking_number: string, status: string, location: ?string, timestamp: string}>>
     */
    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        try {
            $params = [
                'auth' => $this->client->authParams(),
                'startDate' => $since->format('Y-m-d\TH:i:s'),
            ];

            $response = $this->client->callTracking('listStatusUpdates', $params);

            return ServiceResult::ok($this->mapper->toStatusList($response));
        } catch (SoapFault $e) {
            return ServiceResult::fail('yurtici_track_list_error', $e->getMessage());
        }
    }
}
