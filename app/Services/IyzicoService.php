<?php

namespace App\Services;

use App\Support\ServiceResult;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * iyzico 3DS ödeme servisi.
 *
 * Spec Bölüm 0: tüm dönüşler ServiceResult. Debug modda gerçek API'ye gidilmez
 * (yerel geliştirme/test), otomatik "success" ile akış test edilebilir.
 *
 * NOT (kod-dışı, flag): gerçek 3DS uçtan uca doğrulama CANLI iyzico sandbox
 * hesabı (procurement) + tarayıcı gerektirir. İmza/PKI algoritması yayına
 * almadan önce canlı sandbox ile doğrulanmalı; kart verisi ASLA saklanmaz (PCI).
 */
class IyzicoService
{
    private string $apiKey;

    private string $secretKey;

    private string $baseUrl;

    private bool $debugMode;

    public function __construct()
    {
        $this->apiKey = (string) config('services.iyzico.api_key', '');
        $this->secretKey = (string) config('services.iyzico.secret_key', '');
        $this->baseUrl = (string) config('services.iyzico.base_url', 'https://sandbox.iyzipay.com');
        $this->debugMode = (bool) config('services.iyzico.debug', true);
    }

    /**
     * 3DS ödeme başlatır — banka doğrulama formu (threeDSHtmlContent) döner.
     *
     * @param  array<string, mixed>  $params
     * @return ServiceResult<array<string, mixed>>
     */
    public function initializeThreeDSPayment(array $params): ServiceResult
    {
        if ($this->debugMode) {
            /** @var array<string, mixed> $data */
            $data = ['threeDSHtmlContent' => '', 'paymentId' => 'debug_'.uniqid(), 'status' => 'success'];

            return ServiceResult::ok($data);
        }

        return $this->post('/payment/3dsecure/initialize', $params, fn (array $body): array => [
            'threeDSHtmlContent' => base64_decode((string) ($body['threeDSHtmlContent'] ?? ''), true) ?: '',
            'paymentId' => $body['paymentId'] ?? null,
            'status' => $body['status'] ?? 'failure',
        ]);
    }

    /**
     * 3DS callback sonrası ödemeyi tamamlar/doğrular.
     *
     * @param  array<string, mixed>  $params
     * @return ServiceResult<array<string, mixed>>
     */
    public function completeThreeDSPayment(array $params): ServiceResult
    {
        if ($this->debugMode) {
            /** @var array<string, mixed> $data */
            $data = ['paymentId' => (string) ($params['paymentId'] ?? 'debug_'.uniqid()), 'status' => 'success'];

            return ServiceResult::ok($data);
        }

        return $this->post('/payment/3dsecure/auth', $params, fn (array $body): array => [
            'paymentId' => $body['paymentId'] ?? null,
            'status' => $body['status'] ?? 'failure',
        ]);
    }

    /**
     * @param  array<string, mixed>  $params
     * @param  callable(array<string, mixed>): array<string, mixed>  $mapSuccess
     * @return ServiceResult<array<string, mixed>>
     */
    private function post(string $path, array $params, callable $mapSuccess): ServiceResult
    {
        try {
            $response = Http::withHeaders($this->buildHeaders($params))
                ->post($this->baseUrl.$path, $params);

            /** @var array<string, mixed> $body */
            $body = $response->json() ?? [];

            if ($response->successful() && ($body['status'] ?? '') === 'success') {
                return ServiceResult::ok($mapSuccess($body));
            }

            return ServiceResult::fail(
                'iyzico_'.((string) ($body['errorCode'] ?? 'error')),
                (string) ($body['errorMessage'] ?? __('subscription.payment_error')),
                $body,
            );
        } catch (\Throwable $e) {
            Log::error('iyzico request error', ['path' => $path, 'exception' => $e->getMessage()]);

            return ServiceResult::fail('iyzico_exception', (string) __('subscription.payment_error'));
        }
    }

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, string>
     */
    private function buildHeaders(array $params): array
    {
        $randomKey = uniqid();
        $requestBody = (string) json_encode($params);
        $hashStr = $this->apiKey.$randomKey.$this->secretKey.$requestBody;
        $signature = base64_encode(hash('sha256', $hashStr, true));

        return [
            'Authorization' => "IYZWS {$this->apiKey}:{$signature}",
            'x-iyzi-rnd' => $randomKey,
            'Content-Type' => 'application/json',
        ];
    }
}
