<?php

return [
    'code' => 'hepsiburada',
    'name' => 'Hepsiburada',
    'base_url' => [
        'production' => env('HB_PRODUCTION_BASE_URL', 'https://mpop.hepsiburada.com'),
        'stage' => env('HB_STAGE_BASE_URL', 'https://mpop-sit.hepsiburada.com'),
    ],
    'use_stage' => env('HB_USE_STAGE', true),
    'auth' => [
        'type' => 'basic',
    ],
    'webhook' => [
        'allowed_ips' => array_filter(
            explode(',', env('HB_WEBHOOK_ALLOWED_IPS', ''))
        ),
    ],
    'rate_limits' => [
        'default' => ['per_minute' => 120],
    ],
    'capabilities' => [
        'products_sync' => true,
        'orders_sync' => true,
        'orders_webhook' => true,
        'price_update' => true,
        'stock_update' => true,
        'questions_sync' => true,
        'claims_sync' => true,
        'finance_sync' => true,
        'buybox' => false,
        'ads_api' => true,
    ],
    'limits' => [
        'product_title_max' => 100,
        'product_description_max' => 5000,
    ],
];
