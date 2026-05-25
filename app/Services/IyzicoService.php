<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IyzicoService
{
    private string $apiKey;

    private string $secretKey;

    private string $baseUrl;

    private bool $debugMode;

    public function __construct()
    {
        $this->apiKey = config('services.iyzico.api_key', '');
        $this->secretKey = config('services.iyzico.secret_key', '');
        $this->baseUrl = config('services.iyzico.base_url', 'https://sandbox.iyzipay.com');
        $this->debugMode = (bool) config('services.iyzico.debug', true);
    }

    /**
     * Process a payment.
     *
     * In debug mode returns a fake success immediately without hitting the API.
     *
     * @param  array{price: float, currency: string, buyer: array, card: array}  $params
     * @return array{success: bool, reference: string|null, message: string}
     */
    public function createPayment(array $params): array
    {
        if ($this->debugMode) {
            return [
                'success' => true,
                'reference' => 'debug_'.uniqid(),
                'message' => 'Debug mode: payment accepted.',
            ];
        }

        try {
            $response = Http::withHeaders($this->buildHeaders($params))
                ->post("{$this->baseUrl}/payment/auth", $params);

            $body = $response->json();

            if ($response->successful() && ($body['status'] ?? '') === 'success') {
                return [
                    'success' => true,
                    'reference' => $body['paymentId'] ?? null,
                    'message' => 'Payment successful.',
                ];
            }

            return [
                'success' => false,
                'reference' => null,
                'message' => $body['errorMessage'] ?? 'Payment failed.',
            ];
        } catch (\Throwable $e) {
            Log::error('iyzico payment error', ['exception' => $e->getMessage()]);

            return [
                'success' => false,
                'reference' => null,
                'message' => __('subscription.payment_error'),
            ];
        }
    }

    /** @param  array<string, mixed>  $params */
    private function buildHeaders(array $params): array
    {
        $randomKey = uniqid();
        $requestBody = json_encode($params);
        $hashStr = $this->apiKey.$randomKey.$this->secretKey.$requestBody;
        $signature = base64_encode(hash('sha256', $hashStr, true));

        return [
            'Authorization' => "IYZWS {$this->apiKey}:{$signature}",
            'x-iyzi-rnd' => $randomKey,
            'Content-Type' => 'application/json',
        ];
    }
}
