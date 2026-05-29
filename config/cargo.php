<?php

use App\Services\Cargo\Aras\ArasService;
use App\Services\Cargo\Dhl\DhlService;
use App\Services\Cargo\Mng\MngService;
use App\Services\Cargo\Ups\UpsService;
use App\Services\Cargo\Yurtici\YurticiService;

return [

    /*
    |--------------------------------------------------------------------------
    | Kargo Sağlayıcıları
    |--------------------------------------------------------------------------
    |
    | Her kargo firması için endpoint, protokol ve varsayılan değerler.
    | Bkz. CIROTIK_AGENT_SPEC.md Bölüm 8.1
    |
    */
    'providers' => [
        'yurtici' => [
            'name' => 'Yurtiçi Kargo',
            'protocol' => 'soap',
            'class' => YurticiService::class,
            'wsdl' => [
                'test' => env('YURTICI_TEST_WSDL', 'http://testwebservices.yurticikargo.com:9090/KOPSWebServices/ShippingOrderDispatcherServices?wsdl'),
                'production' => env('YURTICI_PRODUCTION_WSDL', 'http://webservices.yurticikargo.com:8080/KOPSWebServices/ShippingOrderDispatcherServices?wsdl'),
            ],
            'tracking_wsdl' => [
                'test' => env('YURTICI_TRACKING_TEST_WSDL', 'http://testwebservices.yurticikargo.com:9090/KOPSWebServices/WsReport?wsdl'),
                'production' => env('YURTICI_TRACKING_PRODUCTION_WSDL', 'http://webservices.yurticikargo.com:8080/KOPSWebServices/WsReport?wsdl'),
            ],
            'use_test' => env('YURTICI_USE_TEST', true),
            'has_webhook' => false,
            'label_formats' => ['a4_pdf', 'zpl'],
            'requires_ip_whitelist' => true,
            'enabled' => true,
        ],

        'aras' => [
            'name' => 'Aras Kargo',
            'protocol' => 'soap',
            'class' => ArasService::class,
            'rest_endpoint' => [
                'production' => env('ARAS_REST_ENDPOINT', 'https://esasweb.araskargo.com.tr'),
            ],
            'has_webhook' => true,
            'webhook_allowed_ips' => array_filter(
                explode(',', env('ARAS_WEBHOOK_ALLOWED_IPS', ''))
            ),
            'label_formats' => ['a4_pdf', 'zpl'],
            'requires_ip_whitelist' => true,
            'enabled' => true,
        ],

        'mng' => [
            'name' => 'MNG Kargo',
            'protocol' => 'soap',
            'class' => MngService::class,
            'wsdl' => [
                'production' => env('MNG_PRODUCTION_WSDL', 'https://onlinesube.mngkargo.com.tr/mngapix/services/IntegrationService?wsdl'),
            ],
            'has_webhook' => false,
            'label_formats' => ['a4_pdf', 'zpl'],
            'requires_ip_whitelist' => true,
            'enabled' => true,
        ],

        'surat' => [
            'name' => 'Sürat Kargo',
            'protocol' => 'soap',
            'wsdl' => [
                'production' => env('SURAT_PRODUCTION_WSDL'),
            ],
            'has_webhook' => false,
            'label_formats' => ['a4_pdf'],
            'requires_ip_whitelist' => true,
            'enabled' => false,
        ],

        'ptt' => [
            'name' => 'PTT Kargo',
            'protocol' => 'rest',
            'rest_endpoint' => [
                'production' => env('PTT_REST_ENDPOINT'),
            ],
            'has_webhook' => false,
            'label_formats' => ['a4_pdf'],
            'requires_ip_whitelist' => true,
            'enabled' => false,
        ],

        'ups' => [
            'name' => 'UPS Türkiye',
            'protocol' => 'rest',
            'class' => UpsService::class,
            'rest_endpoint' => [
                'test' => env('UPS_TEST_ENDPOINT', 'https://wwwcie.ups.com/api'),
                'production' => env('UPS_PRODUCTION_ENDPOINT', 'https://onlinetools.ups.com/api'),
            ],
            'has_webhook' => true,
            'label_formats' => ['a4_pdf', 'zpl', 'png'],
            'requires_ip_whitelist' => false,
            'enabled' => false,
        ],

        'dhl' => [
            'name' => 'DHL',
            'protocol' => 'rest',
            'class' => DhlService::class,
            'rest_endpoint' => [
                'test' => env('DHL_TEST_ENDPOINT', 'https://api-sandbox.dhl.com'),
                'production' => env('DHL_PRODUCTION_ENDPOINT', 'https://api.dhl.com'),
            ],
            'has_webhook' => true,
            'label_formats' => ['a4_pdf', 'zpl', 'png'],
            'requires_ip_whitelist' => false,
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Varsayılan Etiket Ayarları
    |--------------------------------------------------------------------------
    */
    'label_defaults' => [
        'format' => 'a4_pdf',
        'positions_per_page' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Takip Sorgulama Aralığı (dakika)
    |--------------------------------------------------------------------------
    */
    'tracking_poll_interval_minutes' => (int) env('CARGO_TRACKING_POLL_INTERVAL', 60),

    /*
    |--------------------------------------------------------------------------
    | Webhook Olmayan Sağlayıcılar İçin Takip Cron'u Aktif mi?
    |--------------------------------------------------------------------------
    */
    'tracking_poll_enabled' => env('CARGO_TRACKING_POLL_ENABLED', false),
];
