<?php

return [
    'code' => 'trendyol',
    'name' => 'Trendyol',
    'base_url' => [
        'production' => env('TRENDYOL_PRODUCTION_BASE_URL', 'https://apigw.trendyol.com'),
        'stage' => env('TRENDYOL_STAGE_BASE_URL', 'https://stageapigw.trendyol.com'),
    ],
    'use_stage' => env('TRENDYOL_USE_STAGE', false),
    'auth' => [
        'type' => 'basic',
        'user_agent_format' => '{seller_id} - {integrator_name}',
    ],
    'webhook' => [
        'allowed_ips' => array_filter(
            explode(',', env('TRENDYOL_WEBHOOK_ALLOWED_IPS', ''))
        ),
    ],
    'rate_limits' => [
        'default' => ['per_minute' => 600],
        'buybox' => ['per_minute' => 1000],
        'price_inventory' => ['per_minute' => 60],
    ],
    'capabilities' => [
        'products_sync' => true,
        'orders_sync' => true,
        'orders_webhook' => true,
        'price_update' => true,
        'price_update_cooldown_minutes' => 15,
        'stock_update' => true,
        'questions_sync' => true,
        'claims_sync' => true,
        'finance_sync' => true,
        'finance_settlement' => true,
        'buybox' => true,
        'ads_api' => true,
    ],
    'limits' => [
        'product_title_max' => 100,
        'product_description_max' => 30000,
        'bulk_price_inventory_size' => 1000,
    ],
];
