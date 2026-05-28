<?php

namespace App\Services\Marketplaces\Trendyol;

use App\Support\ServiceResult;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Trendyol API HTTP istemcisi. Basic auth + config tabanlı URL +
 * Redis token-bucket rate limit governor.
 *
 * Tüm public metodlar ServiceResult döner — exception fırlatmaz.
 */
class Client
{
    protected string $baseUrl;

    protected string $apiKey;

    protected string $apiSecret;

    protected string $sellerId;

    protected string $userAgent;

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

        $config = config('marketplaces.trendyol', []);

        $stage = $useStage ?? (bool) ($config['use_stage'] ?? false);
        $bases = $config['base_url'] ?? [];
        $this->baseUrl = $stage
            ? ($bases['stage'] ?? 'https://stageapigw.trendyol.com')
            : ($bases['production'] ?? 'https://apigw.trendyol.com');

        $format = $config['auth']['user_agent_format'] ?? '{seller_id} - {integrator_name}';
        $this->userAgent = str_replace(
            ['{seller_id}', '{integrator_name}'],
            [$sellerId, 'Cirotik'],
            $format,
        );

        $this->rateLimits = $config['rate_limits'] ?? ['default' => ['per_minute' => 600]];
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
     * @param  array<string, mixed>  $data  query params (GET) veya JSON body (POST/PUT)
     * @return ServiceResult<array<string, mixed>>
     */
    public function send(string $method, string $path, array $data = []): ServiceResult
    {
        $bucket = str_contains($path, 'buybox-prices') ? 'buybox' : 'default';

        if (! $this->consumeToken($bucket)) {
            return ServiceResult::fail(
                'rate_limited',
                "Trendyol {$bucket} rate limit aşıldı. Lütfen biraz bekleyip tekrar deneyin.",
            );
        }

        $request = $this->newRequest();

        /** @var Response $response */
        $response = match ($method) {
            'get' => $request->get($this->baseUrl.$path, $data),
            'post' => $request->post($this->baseUrl.$path, $data),
            'put' => $request->put($this->baseUrl.$path, $data),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        if ($response->failed()) {
            Log::error('Trendyol API Error', [
                'method' => strtoupper($method),
                'path' => $path,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ServiceResult::fail(
                'api_error',
                'Trendyol API hatası: HTTP '.$response->status(),
                $response->json(),
            );
        }

        return ServiceResult::ok($response->json());
    }

    /**
     * Dakikalık token bucket. Over-limit ise false döner.
     */
    protected function consumeToken(string $bucket = 'default'): bool
    {
        $limit = $this->rateLimits[$bucket]['per_minute']
            ?? $this->rateLimits['default']['per_minute']
            ?? 600;

        $windowKey = (int) floor(now()->timestamp / 60);
        $cacheKey = "trendyol_rl:{$this->sellerId}:{$bucket}:{$windowKey}";

        Cache::add($cacheKey, 0, now()->addMinutes(2));

        $current = Cache::increment($cacheKey);

        return $current <= $limit;
    }

    protected function newRequest(): PendingRequest
    {
        return Http::withBasicAuth($this->apiKey, $this->apiSecret)
            ->withUserAgent($this->userAgent)
            ->timeout(30)
            ->connectTimeout(10)
            ->acceptJson();
    }
}
