<?php

namespace App\Services;

use App\Models\Product;

class TrendyolService extends BaseMarketplaceService
{
    /**
     * Get default headers for Trendyol API requests.
     *
     * @return array
     */
    protected function getDefaultHeaders(): array
    {
        $credentials = base64_encode($this->credential->api_key . ':' . $this->credential->api_secret);
        $supplierId = $this->credential->api_key;

        return [
            'Authorization' => 'Basic ' . $credentials,
            'User-Agent' => "{$supplierId} - Resbe Integration",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];
    }

    /**
     * Get products from Trendyol.
     *
     * @param array $filters
     * @return array
     */
    public function getProducts(array $filters = []): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('products', ['supplierId' => $supplierId]);

        $params = [
            'page' => $filters['page'] ?? 0,
            'size' => $filters['size'] ?? 50,
        ];

        if (isset($filters['approved'])) {
            $params['approved'] = $filters['approved'];
        }

        if (isset($filters['barcode'])) {
            $params['barcode'] = $filters['barcode'];
        }

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Create a product on Trendyol.
     *
     * @param Product $product
     * @return array
     */
    public function createProduct(Product $product): array
    {
        // Validate required fields
        if (!$product->barcode) {
            throw new \Exception('Product barcode is required for Trendyol');
        }

        if (!$product->name) {
            throw new \Exception('Product name is required for Trendyol');
        }

        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('products', ['supplierId' => $supplierId]);

        $attributes = is_array($product->attributes) ? $product->attributes : [];
        $images = is_array($product->images) ? $product->images : [];

        $data = [
            'items' => [
                [
                    'barcode' => $product->barcode,
                    'title' => $product->name,
                    'productMainId' => $attributes['product_main_id'] ?? null,
                    'brandId' => $attributes['brand_id'] ?? null,
                    'categoryId' => $attributes['category_id'] ?? null,
                    'quantity' => $product->stock_quantity ?? 0,
                    'stockCode' => $product->sku,
                    'dimensionalWeight' => $product->dimensional_weight ?? 0,
                    'description' => $product->description ?? '',
                    'currencyType' => $product->currency ?? 'TRY',
                    'listPrice' => $product->base_price ?? 0,
                    'salePrice' => $product->sale_price ?? 0,
                    'vatRate' => $product->vat_rate ?? 18,
                    'cargoCompanyId' => $attributes['cargo_company_id'] ?? 10, // Default: Yurtiçi Kargo
                    'images' => array_map(function ($image) {
                        return ['url' => $image];
                    }, $images),
                    'attributes' => $this->formatAttributes($attributes),
                ]
            ]
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Update a product on Trendyol.
     *
     * @param Product $product
     * @return array
     */
    public function updateProduct(Product $product): array
    {
        // Trendyol uses the same endpoint for create/update
        return $this->createProduct($product);
    }

    /**
     * Update stock quantity for a product.
     *
     * @param string $barcode
     * @param int $quantity
     * @return array
     */
    public function updateStock(string $barcode, int $quantity): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('products', ['supplierId' => $supplierId]);

        $data = [
            'items' => [
                [
                    'barcode' => $barcode,
                    'quantity' => $quantity,
                ]
            ]
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Update price for a product.
     *
     * @param string $barcode
     * @param float $price
     * @return array
     */
    public function updatePrice(string $barcode, float $price): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('products', ['supplierId' => $supplierId]) . '/price-and-inventory';

        $data = [
            'items' => [
                [
                    'barcode' => $barcode,
                    'salePrice' => $price,
                    'listPrice' => $price,
                ]
            ]
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Get orders from Trendyol.
     *
     * @param array $filters
     * @return array
     */
    public function getOrders(array $filters = []): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('orders', ['supplierId' => $supplierId]);

        $params = [
            'page' => $filters['page'] ?? 0,
            'size' => $filters['size'] ?? 50,
        ];

        if (isset($filters['status'])) {
            $params['status'] = $filters['status'];
        }

        if (isset($filters['startDate'])) {
            $params['startDate'] = $filters['startDate'];
        }

        if (isset($filters['endDate'])) {
            $params['endDate'] = $filters['endDate'];
        }

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Update order status.
     *
     * @param string $orderId
     * @param string $status
     * @return array
     */
    public function updateOrderStatus(string $orderId, string $status): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('orders', ['supplierId' => $supplierId]) . '/status';

        $data = [
            'orderNumber' => $orderId,
            'status' => $status,
        ];

        return $this->makeRequest('PUT', $endpoint, $data);
    }

    /**
     * Update tracking number for an order.
     *
     * @param string $orderId
     * @param string $trackingNumber
     * @return array
     */
    public function updateTrackingNumber(string $orderId, string $trackingNumber): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('orders', ['supplierId' => $supplierId]) . '/shipment-packages';

        $data = [
            'orderNumber' => $orderId,
            'trackingNumber' => $trackingNumber,
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Send invoice information for an order.
     *
     * @param string $orderId
     * @param string $invoiceNumber
     * @param string $invoiceLink
     * @return array
     */
    public function sendInvoice(string $orderId, string $invoiceNumber, string $invoiceLink): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('orders', ['supplierId' => $supplierId]) . '/invoice';

        $data = [
            'orderNumber' => $orderId,
            'invoiceNumber' => $invoiceNumber,
            'invoiceLink' => $invoiceLink,
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Get claims/returns from Trendyol.
     *
     * @param array $filters
     * @return array
     */
    public function getClaims(array $filters = []): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('claims', ['supplierId' => $supplierId]);

        $params = [
            'page' => $filters['page'] ?? 0,
            'size' => $filters['size'] ?? 50,
        ];

        if (isset($filters['status'])) {
            $params['status'] = $filters['status'];
        }

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Approve a claim/return.
     *
     * @param string $claimId
     * @return array
     */
    public function approveClaim(string $claimId): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('claims', ['supplierId' => $supplierId]) . "/{$claimId}/approve";

        return $this->makeRequest('PUT', $endpoint);
    }

    /**
     * Reject a claim/return.
     *
     * @param string $claimId
     * @param string $reason
     * @return array
     */
    public function rejectClaim(string $claimId, string $reason): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('claims', ['supplierId' => $supplierId]) . "/{$claimId}/reject";

        $data = [
            'rejectReasonType' => $reason,
        ];

        return $this->makeRequest('PUT', $endpoint, $data);
    }

    /**
     * Get customer questions from Trendyol.
     *
     * @param array $filters
     * @return array
     */
    public function getQuestions(array $filters = []): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('questions', ['supplierId' => $supplierId]);

        $params = [
            'page' => $filters['page'] ?? 0,
            'size' => $filters['size'] ?? 50,
        ];

        if (isset($filters['status'])) {
            $params['status'] = $filters['status'];
        }

        return $this->makeRequest('GET', $endpoint, $params);
    }

    /**
     * Answer a customer question.
     *
     * @param string $questionId
     * @param string $answer
     * @return array
     */
    public function answerQuestion(string $questionId, string $answer): array
    {
        $supplierId = $this->credential->api_key;
        $endpoint = $this->buildEndpoint('questions', ['supplierId' => $supplierId]) . "/{$questionId}/answers";

        $data = [
            'text' => $answer,
        ];

        return $this->makeRequest('POST', $endpoint, $data);
    }

    /**
     * Get categories from Trendyol.
     *
     * @return array
     */
    public function getCategories(): array
    {
        $endpoint = $this->buildEndpoint('categories');
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get brands from Trendyol.
     *
     * @return array
     */
    public function getBrands(): array
    {
        $endpoint = $this->buildEndpoint('brands');
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Get category attributes from Trendyol.
     *
     * @param int $categoryId
     * @return array
     */
    public function getCategoryAttributes(int $categoryId): array
    {
        $endpoint = $this->buildEndpoint('categories') . "/{$categoryId}/attributes";
        return $this->makeRequest('GET', $endpoint);
    }

    /**
     * Format product attributes for Trendyol.
     *
     * @param array $attributes
     * @return array
     */
    protected function formatAttributes(array $attributes): array
    {
        if (empty($attributes)) {
            return [];
        }

        $formatted = [];

        foreach ($attributes as $key => $value) {
            if (is_array($value) && isset($value['attributeId'], $value['attributeValueId'])) {
                $formatted[] = [
                    'attributeId' => (int) $value['attributeId'],
                    'attributeValueId' => (int) $value['attributeValueId'],
                ];
            }
        }

        return $formatted;
    }
}
