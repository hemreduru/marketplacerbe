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
    'commission' => [
        'default_rate' => 15.0,
        'base_type' => 'vat_included',
    ],
    'vat_rates' => [
        'sale_default' => 20,
        'purchase_default' => 20,
        'commission' => 20,
        'shipping' => 20,
    ],
    // 7524 sayılı Kanun: 01.01.2025'ten itibaren aracı platformlarda %1 stopaj (KDV hariç matrah)
    'stopaj' => [
        'rate' => env('STOPAJ_RATE', 1.0),
    ],
    // Tem 2025: İşlem + Hizmet bedeli birleşti → teslim edilen sipariş başına 8 TL + KDV
    'platform_service_fee' => [
        'standard' => [
            'amount_excl_vat' => 8.00,
            'vat_rate' => 20,
        ],
    ],
    'shipping' => [
        // HB barem/desi tarifesi — fallback tahmin; gerçek tutar kargo faturasından gelir
        'default_tariff' => [
            'base_desi' => 5,
            'base_price' => 108.66,
            'per_extra_desi_price' => 8.50,
        ],
    ],
    'limits' => [
        'product_title_max' => 100,
        'product_description_max' => 5000,
    ],
];
