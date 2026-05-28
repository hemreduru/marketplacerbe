<?php

namespace App\Services\Marketplaces\Amazon;

use App\Support\ServiceResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Amazon SP-API istemcisi — OAuth 2.0 LWA + IAM role.
 * MarketplacelD: A33AVAJ2PDY3EV (TR).
 */
class Client
{
    private string $baseUrl;

    private string $lwaClientId;

    private string $lwaClientSecret;

    private string $lwaRefreshToken;

    private string $marketplaceId;

    public function __construct(?string $sellerId = null)
    {
        $config = config('marketplaces.amazon');
        $this->baseUrl = $config['base_url']['production'];
        $this->lwaClientId = $config['auth']['lwa_client_id'] ?? '';
        $this->lwaClientSecret = $config['auth']['lwa_client_secret'] ?? '';
        $this->lwaRefreshToken = $config['auth']['lwa_refresh_token'] ?? '';
        $this->marketplaceId = $config['marketplace_id'] ?? 'A33AVAJ2PDY3EV';
    }

    public function getAccessToken(): string
    {
        return Cache::remember('amz_access_token', 3500, function () {
            $response = Http::asForm()
                ->timeout(15)
                ->connectTimeout(5)
                ->post('https://api.amazon.com/auth/o2/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $this->lwaRefreshToken,
                    'client_id' => $this->lwaClientId,
                    'client_secret' => $this->lwaClientSecret,
                ]);

            if (! $response->successful()) {
                throw new \RuntimeException('Amazon LWA token request failed: '.$response->status());
            }

            $data = $response->json();

            return $data['access_token'];
        });
    }

    public function request(string $method, string $path, array $data = [], array $query = []): ServiceResult
    {
        $writeEnabled = config('marketplaces.amazon.capabilities.price_update') !== false
            || config('marketplaces.amazon.capabilities.stock_update') !== false;

        if (! $writeEnabled && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return ServiceResult::fail('write_disabled', 'Amazon write operations are disabled in this phase');
        }

        $token = $this->getAccessToken();

        $request = Http::withToken($token)
            ->timeout(30)
            ->connectTimeout(10)
            ->withHeaders(['x-amz-marketplace-id' => $this->marketplaceId]);

        $url = $this->baseUrl.$path;

        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }

        $response = match (strtoupper($method)) {
            'GET' => $request->get($url),
            'POST' => $request->post($url, $data),
            default => $request->get($url),
        };

        if (! $response->successful()) {
            return ServiceResult::fail(
                'amazon_api_error',
                $response->reason() ?? 'Amazon API error',
                $response->json(),
            );
        }

        return ServiceResult::ok($response->json());
    }
}
