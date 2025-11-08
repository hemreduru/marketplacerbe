<?php

namespace App\Services;

use App\Models\Marketplace;
use App\Models\MarketplaceSyncLog;
use App\Models\UserMarketplaceCredential;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class BaseMarketplaceService implements MarketplaceServiceInterface
{
    protected UserMarketplaceCredential $credential;
    protected Marketplace $marketplace;
    protected string $apiBaseUrl;
    protected int $timeout;

    public function __construct(UserMarketplaceCredential $credential)
    {
        $this->credential = $credential;

        // Ensure marketplace relationship is loaded
        if (!$credential->relationLoaded('marketplace')) {
            $credential->load('marketplace');
        }

        if (!$credential->marketplace) {
            throw new \Exception('Marketplace not found for credential ID: ' . $credential->id);
        }

        $this->marketplace = $credential->marketplace;
        $this->apiBaseUrl = $this->getApiBaseUrl();
        $this->timeout = $this->getTimeout();
    }

    /**
     * Get the API base URL for this marketplace.
     *
     * @return string
     */
    protected function getApiBaseUrl(): string
    {
        $config = config("marketplace.marketplaces.{$this->marketplace->slug}");

        if (!$config) {
            return $this->marketplace->api_base_url;
        }

        $useStage = $config['use_stage'] ?? false;

        if ($useStage && isset($config['stage_api_url'])) {
            return $config['stage_api_url'];
        }

        return $config['production_api_url'] ?? $this->marketplace->api_base_url;
    }

    /**
     * Get the timeout value for API requests.
     *
     * @return int
     */
    protected function getTimeout(): int
    {
        $config = config("marketplace.marketplaces.{$this->marketplace->slug}");
        return $config['timeout'] ?? 30;
    }

    /**
     * Make an HTTP request to the marketplace API.
     *
     * @param string $method
     * @param string $endpoint
     * @param array $data
     * @param array $headers
     * @return array
     * @throws \Exception
     */
    protected function makeRequest(string $method, string $endpoint, array $data = [], array $headers = []): array
    {
        $startTime = microtime(true);
        $url = $this->apiBaseUrl . $endpoint;

        try {
            $request = Http::timeout($this->timeout)
                ->withHeaders(array_merge($this->getDefaultHeaders(), $headers));

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                'POST' => $request->post($url, $data),
                'PUT' => $request->put($url, $data),
                'DELETE' => $request->delete($url, $data),
                default => throw new \Exception("Unsupported HTTP method: {$method}")
            };

            $duration = (microtime(true) - $startTime) * 1000;

            $responseData = $response->json() ?? [];
            $statusCode = $response->status();

            // Log successful request
            $this->logSync(
                syncType: 'api_request',
                entityType: $this->extractEntityType($endpoint),
                status: $response->successful() ? 'success' : 'failed',
                requestData: ['method' => $method, 'endpoint' => $endpoint, 'data' => $data],
                responseData: $responseData,
                errorMessage: $response->successful() ? null : "HTTP {$statusCode}: " . $response->body(),
                durationMs: (int) $duration
            );

            if (!$response->successful()) {
                throw new \Exception("API request failed with status {$statusCode}: " . $response->body());
            }

            return $responseData;

        } catch (\Exception $e) {
            $duration = (microtime(true) - $startTime) * 1000;

            // Log failed request
            $this->logSync(
                syncType: 'api_request',
                entityType: $this->extractEntityType($endpoint),
                status: 'failed',
                requestData: ['method' => $method, 'endpoint' => $endpoint, 'data' => $data],
                responseData: [],
                errorMessage: $e->getMessage(),
                durationMs: (int) $duration
            );

            Log::error("Marketplace API Error [{$this->marketplace->name}]: " . $e->getMessage(), [
                'marketplace' => $this->marketplace->code,
                'endpoint' => $endpoint,
                'method' => $method,
            ]);

            throw $e;
        }
    }

    /**
     * Get default headers for API requests.
     *
     * @return array
     */
    abstract protected function getDefaultHeaders(): array;

    /**
     * Extract entity type from endpoint.
     *
     * @param string $endpoint
     * @return string
     */
    protected function extractEntityType(string $endpoint): string
    {
        if (str_contains($endpoint, 'product')) return 'product';
        if (str_contains($endpoint, 'order')) return 'order';
        if (str_contains($endpoint, 'claim')) return 'claim';
        if (str_contains($endpoint, 'question')) return 'question';
        if (str_contains($endpoint, 'category')) return 'category';
        if (str_contains($endpoint, 'brand')) return 'brand';

        return 'unknown';
    }

    /**
     * Log synchronization activity.
     *
     * @param string $syncType
     * @param string $entityType
     * @param string $status
     * @param array $requestData
     * @param array $responseData
     * @param string|null $errorMessage
     * @param int $durationMs
     * @param int|null $entityId
     * @return void
     */
    protected function logSync(
        string $syncType,
        string $entityType,
        string $status,
        array $requestData = [],
        array $responseData = [],
        ?string $errorMessage = null,
        int $durationMs = 0,
        ?int $entityId = null
    ): void {
        MarketplaceSyncLog::create([
            'user_id' => $this->credential->user_id,
            'marketplace_id' => $this->marketplace->id,
            'sync_type' => $syncType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => $status,
            'request_data' => $requestData,
            'response_data' => $responseData,
            'error_message' => $errorMessage,
            'duration_ms' => $durationMs,
        ]);
    }

    /**
     * Build endpoint with parameters.
     *
     * @param string $endpointKey
     * @param array $params
     * @return string
     */
    protected function buildEndpoint(string $endpointKey, array $params = []): string
    {
        $config = config("marketplace.marketplaces.{$this->marketplace->slug}");
        $endpoint = $config['endpoints'][$endpointKey] ?? '';

        foreach ($params as $key => $value) {
            $endpoint = str_replace("{{$key}}", $value, $endpoint);
        }

        return $endpoint;
    }

    /**
     * Get marketplace-specific configuration.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return config("marketplace.marketplaces.{$this->marketplace->slug}.{$key}", $default);
    }
}
