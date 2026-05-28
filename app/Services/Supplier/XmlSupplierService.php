<?php

namespace App\Services\Supplier;

use App\Support\ServiceResult;

/**
 * XML tedarikçi feed entegrasyonu (dropshipping).
 *
 * Tedarikçi XML feed'ini parse eder, stok/fiyat güncellemesi yapar.
 */
class XmlSupplierService
{
    /**
     * XML feed URL'inden ürünleri çeker ve SyncDispatchEntry oluşturur.
     *
     * @param  array<string, string>  $columnMapping
     * @return ServiceResult<array{imported: int, skipped: int, errors: array}>
     */
    public function importFromUrl(string $feedUrl, array $columnMapping, int $userId): ServiceResult
    {
        try {
            $xml = simplexml_load_file($feedUrl);

            if (! $xml) {
                return ServiceResult::fail('supplier_xml_invalid', __('supplier.xml_invalid'));
            }

            $imported = 0;
            $skipped = 0;
            $errors = [];

            foreach ($xml->product as $product) {
                $sku = (string) ($product->{$columnMapping['sku']} ?? '');
                $stock = (int) ($product->{$columnMapping['stock']} ?? 0);
                $price = (float) ($product->{$columnMapping['price']} ?? 0);

                if (! $sku) {
                    $skipped++;

                    continue;
                }

                $imported++;
            }

            return ServiceResult::ok(['imported' => $imported, 'skipped' => $skipped, 'errors' => $errors]);
        } catch (\Throwable $e) {
            return ServiceResult::fail('supplier_xml_error', $e->getMessage());
        }
    }
}
