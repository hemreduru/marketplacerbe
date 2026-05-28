<?php

namespace App\Services\Marketplaces\N11;

use App\Support\ServiceResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use SoapClient;
use SoapFault;

class Client
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    /** @var array<string, array{per_minute: int}> */
    protected array $rateLimits;

    /** @var array<string, SoapClient> */
    protected array $clients = [];

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $sellerId = '',
        ?bool $useStage = null,
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;

        $config = config('marketplaces.n11', []);
        $bases = $config['base_url'] ?? [];
        $this->baseUrl = $bases['production'] ?? 'https://api.n11.com/ws';
        $this->rateLimits = $config['rate_limits'] ?? ['default' => ['per_minute' => 100]];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param  array<string, mixed>  $params
     * @return ServiceResult<array<string, mixed>>
     */
    public function call(string $service, string $method, array $params = []): ServiceResult
    {
        if (! $this->consumeToken()) {
            return ServiceResult::fail('rate_limited', 'N11 rate limit aşıldı.');
        }

        try {
            $client = $this->getSoapClient($service);

            $auth = [
                'auth' => [
                    'appKey' => $this->apiKey,
                    'appSecret' => $this->apiSecret,
                ],
            ];

            $result = $client->__soapCall($method, [array_merge($auth, $params)]);

            return ServiceResult::ok(json_decode(json_encode($result), true) ?: []);
        } catch (SoapFault $e) {
            Log::error('N11 SOAP Error', [
                'service' => $service,
                'method' => $method,
                'message' => $e->getMessage(),
            ]);

            return ServiceResult::fail('soap_error', 'N11 SOAP hatası: '.$e->getMessage());
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return ServiceResult<array<string, mixed>>
     */
    public function get(string $path, array $params = []): ServiceResult
    {
        // N11 SOAP tabanlı; REST-style çağrı yok.
        // Bu method interface uyumu için placeholder.
        return ServiceResult::fail('not_implemented', 'N11 REST çağrıları desteklemez. call() kullanın.');
    }

    protected function getSoapClient(string $service): SoapClient
    {
        if (! isset($this->clients[$service])) {
            $wsdl = $this->baseUrl.'/'.$service.'?wsdl';

            $this->clients[$service] = new SoapClient($wsdl, [
                'trace' => true,
                'exceptions' => true,
                'connection_timeout' => 30,
            ]);
        }

        return $this->clients[$service];
    }

    protected function consumeToken(): bool
    {
        $limit = $this->rateLimits['default']['per_minute'] ?? 100;
        $windowKey = (int) floor(now()->timestamp / 60);
        $cacheKey = "n11_rl:{$this->apiKey}:{$windowKey}";

        Cache::add($cacheKey, 0, now()->addMinutes(2));
        $current = Cache::increment($cacheKey);

        return $current <= $limit;
    }
}
