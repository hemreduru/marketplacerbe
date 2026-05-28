<?php

namespace App\Services\EFatura\BizimHesap;

use App\Models\EInvoiceCredential;
use App\Services\EFatura\Contracts\EInvoiceProvider;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Http;

class BizimHesapService implements EInvoiceProvider
{
    private string $baseUrl;

    public function __construct(
        private readonly EInvoiceCredential $credential,
    ) {
        $this->baseUrl = config('efatura.providers.bizim_hesap.base_url', 'https://api.bizimhesap.com');
    }

    public function createInvoice(array $invoiceData): ServiceResult
    {
        try {
            $response = Http::withToken($this->credential->api_key)
                ->timeout(30)
                ->post($this->baseUrl.'/invoices', $invoiceData)
                ->json();

            $invoiceUuid = $response['id'] ?? null;

            if (! $invoiceUuid) {
                return ServiceResult::fail('bizimhesap_create_failed', 'Fatura oluşturulamadı.');
            }

            return ServiceResult::ok(['invoice_uuid' => $invoiceUuid, 'e_invoice_number' => $response['invoice_number'] ?? null]);
        } catch (\Throwable $e) {
            return ServiceResult::fail('bizimhesap_api_error', $e->getMessage());
        }
    }

    public function cancelInvoice(string $invoiceUuid): ServiceResult
    {
        try {
            Http::withToken($this->credential->api_key)
                ->post($this->baseUrl."/invoices/{$invoiceUuid}/cancel");

            return ServiceResult::ok(null);
        } catch (\Throwable $e) {
            return ServiceResult::fail('bizimhesap_cancel_error', $e->getMessage());
        }
    }

    public function getInvoicePdf(string $invoiceUuid): ServiceResult
    {
        try {
            $response = Http::withToken($this->credential->api_key)
                ->get($this->baseUrl."/invoices/{$invoiceUuid}/pdf")
                ->body();

            return ServiceResult::ok($response);
        } catch (\Throwable $e) {
            return ServiceResult::fail('bizimhesap_pdf_error', $e->getMessage());
        }
    }

    public function getInvoiceStatus(string $invoiceUuid): ServiceResult
    {
        try {
            $response = Http::withToken($this->credential->api_key)
                ->get($this->baseUrl."/invoices/{$invoiceUuid}")
                ->json();

            return ServiceResult::ok([
                'status' => $response['status'] ?? 'unknown',
                'e_invoice_number' => $response['invoice_number'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return ServiceResult::fail('bizimhesap_status_error', $e->getMessage());
        }
    }

    public function getProviderCode(): string
    {
        return 'bizim_hesap';
    }

    public function getCapabilities(): array
    {
        return ['efatura' => true, 'earsiv' => true, 'cancel' => true];
    }
}
