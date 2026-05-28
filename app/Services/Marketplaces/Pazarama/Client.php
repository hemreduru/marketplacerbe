<?php

namespace App\Services\Marketplaces\Pazarama;

use App\Support\ServiceResult;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Client
{
    protected string $baseUrl;

    protected string $clientId;

    protected string $clientSecret;

    /** @var array<string, array{per_minute: int}> */
    protected array $rateLimits;

    public function __construct(
        string $clientId,
        string $clientSecret,
        string $sellerId = '',
        ?bool $useStage = null,
    ) {
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;

        $config = config('marketplaces.pazarama', []);
        $bases = $config['base_url'] ?? [];
        $this->baseUrl = $bases['production'] ?? 'https://isortagimapi.pazarama.com';
        $this->rateLimits = $config['rate_limits'] ?? ['default' => ['per_minute' => 60]];
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
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
            return ServiceResult::fail('rate_limited', 'Pazarama rate limit aşıldı.');
        }

        $tokenResult = $this->getAccessToken();
        if (! $tokenResult->ok) {
            return $tokenResult;
        }

        $request = Http::withToken($tokenResult->data)
            ->timeout(30)
            ->connectTimeout(10)
            ->acceptJson();

        /** @var Response $response */
        $response = match ($method) {
            'get' => $request->get($this->baseUrl.$path, $data),
            'post' => $request->post($this->baseUrl.$path, $data),
            'put' => $request->put($this->baseUrl.$path, $data),
            default => throw new \InvalidArgumentException("Unsupported method: {$method}"),
        };

        if ($response->failed()) {
            Log::error('Pazarama API Error', [
                'method' => strtoupper($method),
                'path' => $path,
                'status' => $response->status(),
            ]);

            return ServiceResult::fail('api_error', 'Pazarama API hatası: HTTP '.$response->status());
        }

        return ServiceResult::ok($response->json());
    }

    /**
     * OAuth2 client_credentials token. 1 saat cache, auto-refresh.
     *
     * @return ServiceResult<string>
     */
    protected function getAccessToken(): ServiceResult
    {
        $cacheKey = "pazarama_token:{$this->clientId}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return ServiceResult::ok($cached);
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->post($this->baseUrl.'/token', [
                    'grant_type' => 'client_credentials',
                    'client_id' => $this->clientId,
                    'client_secret' => $this->clientSecret,
                ]);

            if ($response->failed()) {
                return ServiceResult::fail('auth_error', 'Pazarama token alınamadı: HTTP '.$response->status());
            }

            $token = $response->json('access_token');
            if (empty($token)) {
                return ServiceResult::fail('auth_error', 'Pazarama token response\'unda access_token yok.');
            }

            Cache::put($cacheKey, $token, now()->addSeconds(3500));

            return ServiceResult::ok($token);
        } catch (\Exception $e) {
            return ServiceResult::fail('auth_error', 'Pazarama token alınamadı: '.$e->getMessage());
        }
    }

    protected function consumeToken(): bool
    {
        $limit = $this->rateLimits['default']['per_minute'] ?? 60;
        $windowKey = (int) floor(now()->timestamp / 60);
        $cacheKey = "pazarama_rl:{$this->clientId}:{$windowKey}";

        Cache::add($cacheKey, 0, now()->addMinutes(2));
        $current = Cache::increment($cacheKey);

        return $current <= $limit;
    }
}
