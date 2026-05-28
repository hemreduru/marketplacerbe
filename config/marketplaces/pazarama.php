<?php

return [
    'code' => 'pazarama',
    'name' => 'Pazarama',
    'base_url' => [
        'production' => env('PAZARAMA_BASE_URL', 'https://isortagimapi.pazarama.com'),
    ],
    'use_stage' => false,
    'auth' => [
        'type' => 'oauth_client_credentials',
        'token_endpoint' => '/token',
        'token_ttl_seconds' => 3600,
    ],
    'webhook' => null,
    'rate_limits' => [
        'default' => ['per_minute' => 60],
    ],
    'capabilities' => [
        'products_sync' => true,
        'orders_sync' => true,
        'orders_webhook' => false,
        'price_update' => true,
        'stock_update' => true,
        'questions_sync' => false,
        'claims_sync' => true,
        'finance_sync' => false,
        'buybox' => false,
        'ads_api' => false,
    ],
    'limits' => [
        'product_title_max' => 100,
        'polling_interval_seconds' => 600,
    ],
];
