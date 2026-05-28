<?php

namespace App\Services\Cargo\Mng;

use App\Services\Cargo\Mng\Mapper\TrackingMapper;
use App\Support\ServiceResult;
use DateTimeInterface;
use SoapFault;

class TrackingService
{
    public function __construct(
        private readonly Client $client,
        private readonly TrackingMapper $mapper,
    ) {}

    /**
     * @return ServiceResult<array{status: string, location: ?string, timestamp: string}>
     */
    public function track(string $trackingNumber): ServiceResult
    {
        try {
            $response = $this->client->call('queryTracking', [
                'auth' => $this->client->authParams(),
                'trackingNumber' => $trackingNumber,
            ]);

            return ServiceResult::ok($this->mapper->toTrackingResult($response));
        } catch (SoapFault $e) {
            return ServiceResult::fail('mng_track_error', $e->getMessage());
        }
    }

    /**
     * @return ServiceResult<array<int, array{tracking_number: string, status: string, location: ?string, timestamp: string}>>
     */
    public function listStatusUpdates(DateTimeInterface $since): ServiceResult
    {
        try {
            $response = $this->client->call('listStatusUpdates', [
                'auth' => $this->client->authParams(),
                'startDate' => $since->format('Y-m-d\TH:i:s'),
            ]);

            return ServiceResult::ok($this->mapper->toStatusList($response));
        } catch (SoapFault $e) {
            return ServiceResult::fail('mng_track_list_error', $e->getMessage());
        }
    }
}
