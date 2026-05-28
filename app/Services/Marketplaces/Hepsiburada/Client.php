<?php

namespace App\Services\Marketplaces\Hepsiburada;

use App\Support\ServiceResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    protected string $sellerId;

    /** @var array<string, array{per_minute: int}> */
    protected array $rateLimits;

    public function __construct(
        string $apiKey,
        string $apiSecret,
        string $sellerId,
        ?bool $useStage = null,
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->sellerId = $sellerId;

        $config = config('marketplaces.hepsiburada', []);
        $stage = $useStage ?? (bool) ($config['use_stage'] ?? true);
        $bases = $config['base_url'] ?? [];
        $this->baseUrl = $stage
            ? ($bases['stage'] ?? 'https://mpop-sit.hepsiburada.com')
            : ($bases['production'] ?? 'https://mpop.hepsiburada.com');

        $this->rateLimits = $config['rate_limits'] ?? ['default' => ['per_minute' => 120]];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getSellerId(): string
    {
        return $this->sellerId;
    }

    /**
     * @param  array<string, mixed>  $query
     * @return ServiceResult<array<string, mixed>>
     */
    public function get(string $path, array $query = []): ServiceResult
    {
        return $this->send('get', $path, $query);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return ServiceResult<array<string, mixed>>
     */
    public function post(string $path, array $body = []): ServiceResult
    {
        return $this->send('post', $path, $body);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return ServiceResult<array<string, mixed>>
     */
    public function put(string $path, array $body = []): ServiceResult
    {
        return $this->send('put', $path, $body);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return ServiceResult<array<string, mixed>>
     */
    public function send(string $method, string $path, array $data = []): ServiceResult
    {
        if (! $this->consumeToken()) {
            return ServiceResult::fail('rate_limited', 'Hepsiburada rate limit aşıldı.');
        }

        $request = $this->newRequest();

        /** @var Response $response */
        $response = match ($method) {
            'get' => $request->get($this->baseUrl.$path, $data),
            'post' => $request->post($this->baseUrl.$path, $data),
            'put' => $request->put($this->baseUrl.$path, $data),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        if ($response->failed()) {
            Log::error('Hepsiburada API Error', [
                'method' => strtoupper($method),
                'path' => $path,
                'status' => $response->status(),
            ]);

            return ServiceResult::fail('api_error', 'Hepsiburada API hatası: HTTP '.$response->status());
        }

        return ServiceResult::ok($response->json());
    }

    protected function consumeToken(): bool
    {
        $limit = $this->rateLimits['default']['per_minute'] ?? 120;
        $windowKey = (int) floor(now()->timestamp / 60);
        $cacheKey = "hb_rl:{$this->sellerId}:{$windowKey}";

        Cache::add($cacheKey, 0, now()->addMinutes(2));
        $current = Cache::increment($cacheKey);

        return $current <= $limit;
    }

    protected function newRequest(): PendingRequest
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->timeout(30)
            ->connectTimeout(10)
            ->acceptJson();
    }
}
