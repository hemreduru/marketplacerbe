<?php

namespace App\Services\Marketplaces\Trendyol\Mapper;

use App\Models\Order;
use App\Models\OrderItem;

/**
 * Trendyol sipariş verisini Cirotik iç formatına dönüştürür.
 */
class OrderMapper
{
    /**
     * Trendyol order → Order fillable.
     *
     * @param  array<string, mixed>  $orderData
     * @return array<string, mixed>
     */
    public function toOrderAttributes(array $orderData): array
    {
        return [
            'customer_first_name' => isset($orderData['customerFirstName'])
                ? mb_convert_case($orderData['customerFirstName'], MB_CASE_TITLE, 'UTF-8')
                : null,
            'customer_last_name' => isset($orderData['customerLastName'])
                ? mb_convert_case($orderData['customerLastName'], MB_CASE_TITLE, 'UTF-8')
                : null,
            'customer_email' => $orderData['customerEmail'] ?? null,
            'total_amount' => $orderData['totalPrice'] ?? 0,
            'currency_code' => $orderData['currencyCode'] ?? 'TRY',
            'status' => $orderData['status'] ?? 'Created',
            'shipment_package_status' => $orderData['shipmentPackageStatus'] ?? null,
            'cargo_tracking_number' => $orderData['cargoTrackingNumber'] ?? null,
            'cargo_provider_name' => $orderData['cargoProviderName'] ?? null,
            'raw_data' => $orderData,
        ];
    }

    /**
     * Trendyol line item → OrderItem fillable.
     *
     * @param  array<string, mixed>  $line
     * @return array<string, mixed>
     */
    public function toOrderItemAttributes(array $line): array
    {
        return [
            'product_name' => $line['productName'] ?? 'Unknown',
            'merchant_sku' => $line['merchantSku'] ?? null,
            'barcode' => $line['barcode'] ?? null,
            'quantity' => $line['quantity'] ?? 1,
            'price' => $line['price'] ?? 0,
            'currency_code' => $line['currencyCode'] ?? 'TRY',
            'line_item_status' => $line['orderLineItemStatusName'] ?? null,
        ];
    }
}
