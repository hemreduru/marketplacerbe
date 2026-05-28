<?php

return [
    'code' => 'n11',
    'name' => 'n11',
    'base_url' => [
        'production' => env('N11_BASE_URL', 'https://api.n11.com/ws'),
    ],
    'use_stage' => false,
    'auth' => [
        'type' => 'soap_api_key',
    ],
    'webhook' => null, // N11'de webhook yok — polling
    'rate_limits' => [
        'default' => ['per_minute' => 100],
    ],
    'capabilities' => [
        'products_sync' => true,
        'orders_sync' => true,
        'orders_webhook' => false,
        'price_update' => true,
        'stock_update' => true,
        'questions_sync' => true,
        'claims_sync' => true,
        'finance_sync' => true,
        'buybox' => false,
        'ads_api' => false,
    ],
    'wsdl_endpoints' => [
        'product' => 'ProductService',
        'product_stock' => 'ProductStockService',
        'product_selling' => 'ProductSellingService',
        'order' => 'OrderService',
        'category' => 'CategoryService',
        'shipment_company' => 'ShipmentCompanyService',
        'claims' => 'ClaimsService',
        'order_cargo' => 'OrderCargoService',
    ],
    'commission' => [
        'default_rate' => 15.0,
        'base_type' => 'vat_included',
    ],
    'limits' => [
        'product_title_max' => 100,
        'polling_interval_seconds' => 300,
    ],
];
