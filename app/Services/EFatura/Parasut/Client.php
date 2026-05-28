<?php

namespace App\Services\EFatura\Parasut;

use App\Models\EInvoiceCredential;
use Illuminate\Support\Facades\Http;

class Client
{
    private string $baseUrl;

    public function __construct(
        private readonly EInvoiceCredential $credential,
    ) {
        $this->baseUrl = config('efatura.providers.parasut.base_url', 'https://api.parasut.com/v4');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function post(string $endpoint, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode($this->credential->api_key.':'.$this->credential->api_secret),
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->connectTimeout(10)
            ->retry(2, 500)
            ->post($this->baseUrl.$endpoint, $data);

        return $response->json() ?: [];
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $endpoint, array $params = []): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Basic '.base64_encode($this->credential->api_key.':'.$this->credential->api_secret),
            'Accept' => 'application/json',
        ])
            ->timeout(30)
            ->connectTimeout(10)
            ->get($this->baseUrl.$endpoint, $params);

        return $response->json() ?: [];
    }
}
