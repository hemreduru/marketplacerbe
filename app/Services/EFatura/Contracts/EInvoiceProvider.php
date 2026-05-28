<?php

namespace App\Services\EFatura\Contracts;

use App\Support\ServiceResult;

interface EInvoiceProvider
{
    /**
     * @param  array<string, mixed>  $invoiceData
     * @return ServiceResult<array{invoice_uuid: string, e_invoice_number: ?string}>
     */
    public function createInvoice(array $invoiceData): ServiceResult;

    /**
     * @return ServiceResult<null>
     */
    public function cancelInvoice(string $invoiceUuid): ServiceResult;

    /**
     * @return ServiceResult<string> PDF binary data
     */
    public function getInvoicePdf(string $invoiceUuid): ServiceResult;

    /**
     * @return ServiceResult<array{status: string, e_invoice_number: ?string}>
     */
    public function getInvoiceStatus(string $invoiceUuid): ServiceResult;

    public function getProviderCode(): string;

    /**
     * @return array{efatura: bool, earsiv: bool, cancel: bool}
     */
    public function getCapabilities(): array;
}
