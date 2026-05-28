<?php

namespace App\Services\EFatura\Parasut;

use App\Models\EInvoiceCredential;
use App\Services\EFatura\Contracts\EInvoiceProvider;
use App\Services\EFatura\Parasut\Mapper\InvoiceMapper;
use App\Support\ServiceResult;

class ParasutService implements EInvoiceProvider
{
    private Client $client;

    private InvoiceMapper $mapper;

    public function __construct(EInvoiceCredential $credential)
    {
        $this->client = new Client($credential);
        $this->mapper = new InvoiceMapper;
    }

    public function createInvoice(array $invoiceData): ServiceResult
    {
        try {
            $payload = $this->mapper->toParasutPayload($invoiceData);
            $response = $this->client->post('/e_invoices', $payload);

            $invoiceUuid = $response['data']['id'] ?? null;

            if (! $invoiceUuid) {
                return ServiceResult::fail('parasut_invoice_failed', __('efatura.invoice_create_failed'), $response);
            }

            return ServiceResult::ok([
                'invoice_uuid' => $invoiceUuid,
                'e_invoice_number' => $response['data']['attributes']['invoice_no'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return ServiceResult::fail('parasut_api_error', $e->getMessage());
        }
    }

    public function cancelInvoice(string $invoiceUuid): ServiceResult
    {
        try {
            $this->client->post("/e_invoices/{$invoiceUuid}/cancel", []);

            return ServiceResult::ok(null);
        } catch (\Throwable $e) {
            return ServiceResult::fail('parasut_cancel_error', $e->getMessage());
        }
    }

    public function getInvoicePdf(string $invoiceUuid): ServiceResult
    {
        try {
            $response = $this->client->get("/e_invoices/{$invoiceUuid}/pdf");

            if (empty($response)) {
                return ServiceResult::fail('parasut_pdf_empty', __('efatura.pdf_failed'));
            }

            return ServiceResult::ok(json_encode($response));
        } catch (\Throwable $e) {
            return ServiceResult::fail('parasut_pdf_error', $e->getMessage());
        }
    }

    public function getInvoiceStatus(string $invoiceUuid): ServiceResult
    {
        try {
            $response = $this->client->get("/e_invoices/{$invoiceUuid}");

            return ServiceResult::ok([
                'status' => $response['data']['attributes']['status'] ?? 'unknown',
                'e_invoice_number' => $response['data']['attributes']['invoice_no'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return ServiceResult::fail('parasut_status_error', $e->getMessage());
        }
    }

    public function getProviderCode(): string
    {
        return 'parasut';
    }

    public function getCapabilities(): array
    {
        return [
            'efatura' => true,
            'earsiv' => true,
            'cancel' => true,
        ];
    }
}
