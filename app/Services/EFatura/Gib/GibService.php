<?php

namespace App\Services\EFatura\Gib;

use App\Models\EInvoiceCredential;
use App\Services\EFatura\Contracts\EInvoiceProvider;
use App\Support\ServiceResult;

/**
 * GIB direkt e-arşiv entegrasyonu (Foriba/Logo eFinans).
 *
 * NOT: GIB entegrasyonu mali mühür ve resmi başvuru gerektirir.
 * Bu sınıf temel scaffold'tur — canlıya almadan önce resmi entegratör ile test edilmeli.
 */
class GibService implements EInvoiceProvider
{
    public function __construct(
        private readonly EInvoiceCredential $credential,
    ) {}

    public function createInvoice(array $invoiceData): ServiceResult
    {
        return ServiceResult::fail('gib_not_implemented', 'GIB direkt entegrasyonu henüz aktif değil.');
    }

    public function cancelInvoice(string $invoiceUuid): ServiceResult
    {
        return ServiceResult::fail('gib_not_implemented', 'GIB direkt entegrasyonu henüz aktif değil.');
    }

    public function getInvoicePdf(string $invoiceUuid): ServiceResult
    {
        return ServiceResult::fail('gib_not_implemented', 'GIB direkt entegrasyonu henüz aktif değil.');
    }

    public function getInvoiceStatus(string $invoiceUuid): ServiceResult
    {
        return ServiceResult::fail('gib_not_implemented', 'GIB direkt entegrasyonu henüz aktif değil.');
    }

    public function getProviderCode(): string
    {
        return 'gib_direct';
    }

    public function getCapabilities(): array
    {
        return ['efatura' => false, 'earsiv' => false, 'cancel' => false];
    }
}
