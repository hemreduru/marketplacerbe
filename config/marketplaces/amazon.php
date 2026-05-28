<?php

return [
    'code' => 'amazon',
    'name' => 'Amazon TR',
    'base_url' => [
        'production' => env('AMZ_BASE_URL', 'https://sellingpartnerapi-eu.amazon.com'),
    ],
    'use_stage' => false,
    'marketplace_id' => 'A33AVAJ2PDY3EV',
    'auth' => [
        'type' => 'lwa_oauth',
        'lwa_client_id' => env('AMZ_LWA_CLIENT_ID'),
        'lwa_client_secret' => env('AMZ_LWA_CLIENT_SECRET'),
        'lwa_refresh_token' => env('AMZ_LWA_REFRESH_TOKEN'),
        'aws_role_arn' => env('AMZ_AWS_ROLE_ARN'),
    ],
    'webhook' => null, // SQS Notifications API (PR Faz 2)
    'rate_limits' => [
        'default' => ['per_minute' => 30],
    ],
    'capabilities' => [
        'products_sync' => true,
        'orders_sync' => true,
        'orders_webhook' => false,
        'price_update' => false, // read-only (Faz 2)
        'stock_update' => false,
        'questions_sync' => false,
        'claims_sync' => false,
        'finance_sync' => true,
        'buybox' => false,
        'ads_api' => false,
    ],
];
