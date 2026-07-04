<?php

namespace App\Services\Marketplaces\Hepsiburada\Mapper;

use Carbon\Carbon;

/**
 * Hepsiburada sipariş verisini Cirotik iç formatına dönüştürür.
 *
 * HB tarihleri ISO-8601 string döner (Trendyol ms timestamp kullanır);
 * para alanları {amount, currency} objesi olabilir.
 */
class OrderMapper
{
    /**
     * HB order → Order fillable.
     *
     * @param  array<string, mixed>  $orderData
     * @return array<string, mixed>
     */
    public function toOrderAttributes(array $orderData): array
    {
        [$firstName, $lastName] = $this->customerNames($orderData);

        return [
            'customer_first_name' => $firstName,
            'customer_last_name' => $lastName,
            'customer_email' => $orderData['customerEmail'] ?? $orderData['customer']['email'] ?? null,
            'total_amount' => $this->money($orderData['totalPrice'] ?? $orderData['totalAmount'] ?? 0),
            'currency_code' => 'TRY',
            'status' => $orderData['status'] ?? 'Open',
            'shipment_package_status' => $orderData['packageStatus'] ?? null,
            'cargo_tracking_number' => $orderData['cargoTrackingNumber'] ?? $orderData['barcode'] ?? null,
            'cargo_provider_name' => $orderData['cargoCompany'] ?? $orderData['cargoProviderName'] ?? null,
            'raw_data' => $orderData,
        ];
    }

    /**
     * HB order line → OrderItem fillable.
     *
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public function toOrderItemAttributes(array $line): array
    {
        return [
            'product_name' => $line['productName'] ?? $line['name'] ?? 'Unknown',
            'merchant_sku' => isset($line['merchantSku']) ? (string) $line['merchantSku'] : ($line['sku'] ?? null),
            'barcode' => $line['productBarcode'] ?? $line['barcode'] ?? null,
            'quantity' => (int) ($line['quantity'] ?? 1),
            'price' => $this->money($line['price'] ?? $line['unitPrice'] ?? $line['totalPrice'] ?? 0),
            'currency_code' => 'TRY',
            'line_item_status' => $line['status'] ?? $line['lineItemStatus'] ?? null,
        ];
    }

    /**
     * HB order tarihini parse eder — ISO string veya ms timestamp.
     */
    public function parseDate(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return Carbon::createFromTimestampMs((int) $value);
        }

        try {
            return Carbon::parse((string) $value);
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $orderData
     * @return array{0: string|null, 1: string|null}
     */
    protected function customerNames(array $orderData): array
    {
        $first = $orderData['customerFirstName'] ?? null;
        $last = $orderData['customerLastName'] ?? null;

        if ($first === null && $last === null) {
            $full = trim((string) ($orderData['customerName'] ?? $orderData['customer']['name'] ?? ''));
            if ($full !== '') {
                $parts = preg_split('/\s+/', $full) ?: [];
                $last = count($parts) > 1 ? array_pop($parts) : null;
                $first = implode(' ', $parts) ?: ($last ?? null);
                if ($first === null && $last !== null) {
                    [$first, $last] = [$last, null];
                }
            }
        }

        $title = static fn (?string $v): ?string => $v !== null
            ? mb_convert_case($v, MB_CASE_TITLE, 'UTF-8')
            : null;

        return [$title($first), $title($last)];
    }

    /**
     * Scalar veya {amount, currency} para objesini scalar'a indirger.
     */
    protected function money(mixed $value): float|int|string
    {
        if (is_array($value)) {
            return $value['amount'] ?? 0;
        }

        return $value ?? 0;
    }
}
