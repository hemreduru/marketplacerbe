<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Currency
    |--------------------------------------------------------------------------
    |
    | The default currency for products and pricing across all marketplaces.
    | This is used when no specific currency is provided.
    |
    */

    'default_currency' => env('MARKETPLACE_DEFAULT_CURRENCY', 'TRY'),

    /*
    |--------------------------------------------------------------------------
    | Write Operations Enabled
    |--------------------------------------------------------------------------
    |
    | Master switch for any operation that mutates data on the marketplace
    | (order status updates, price/stock updates, claim approvals, answering
    | customer questions, sending invoices). When disabled, these actions are
    | simulated and never reach the live marketplace. Keep this OFF unless the
    | seller has explicitly authorized live writes.
    |
    */

    'write_enabled' => env('MARKETPLACE_WRITE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Default VAT Rate
    |--------------------------------------------------------------------------
    |
    | The default VAT (Value Added Tax) rate in percentage.
    | Turkey's standard VAT rate is 18%.
    |
    */

    'default_vat_rate' => env('MARKETPLACE_DEFAULT_VAT_RATE', 18),

    /*
    |--------------------------------------------------------------------------
    | Sync Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for marketplace synchronization operations including
    | batch sizes, retry attempts, and timeout values.
    |
    */

    'sync' => [
        'batch_size' => env('MARKETPLACE_SYNC_BATCH_SIZE', 100),
        'retry_attempts' => env('MARKETPLACE_SYNC_RETRY_ATTEMPTS', 3),
        'retry_delay_seconds' => env('MARKETPLACE_SYNC_RETRY_DELAY', 5),
        'timeout_seconds' => env('MARKETPLACE_SYNC_TIMEOUT', 30),
        'max_products_per_request' => env('MARKETPLACE_SYNC_MAX_PRODUCTS', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace-Specific Settings
    |--------------------------------------------------------------------------
    |
    | Configuration specific to each marketplace integration including
    | API URLs, rate limits, and marketplace-specific options.
    |
    */

    'marketplaces' => [
        'trendyol' => [
            'name' => 'Trendyol',
            'code' => 'TRENDYOL',
            'production_api_url' => 'https://apigw.trendyol.com',
            'stage_api_url' => 'https://stageapigw.trendyol.com',
            'use_stage' => env('TRENDYOL_USE_STAGE', false),
            'rate_limit' => [
                'requests_per_minute' => 60,
                'requests_per_hour' => 3600,
            ],
            'user_agent_format' => '{supplier_id} - {integrator_name}',
            'auth_type' => 'basic',
            'timeout' => 30,
            'endpoints' => [
                'products' => '/integration/product/sellers/{sellerId}/products',
                'price_inventory' => '/integration/inventory/sellers/{sellerId}/products/price-and-inventory',
                'product_batch' => '/integration/product/sellers/{sellerId}/products/batch-requests/{batchRequestId}',
                'orders' => '/integration/order/sellers/{sellerId}/orders',
                'claims' => '/integration/order/sellers/{sellerId}/claims',
                'questions' => '/integration/qna/sellers/{sellerId}/questions/filter',
                'settlements' => '/integration/finance/che/sellers/{sellerId}/settlements',
                'otherfinancials' => '/integration/finance/che/sellers/{sellerId}/otherfinancials',
                'brands' => '/integration/product/brands',
                'categories' => '/integration/product/product-categories',
            ],
        ],
        'hepsiburada' => [
            'name' => 'Hepsiburada',
            'code' => 'HEPSIBURADA',
            'production_api_url' => 'https://mpop.hepsiburada.com',
            'stage_api_url' => 'https://mpop-sit.hepsiburada.com',
            'use_stage' => env('HEPSIBURADA_USE_STAGE', false),
            'rate_limit' => [
                'requests_per_minute' => 60,
                'requests_per_hour' => 3600,
            ],
            'auth_type' => 'bearer',
            'timeout' => 30,
            'endpoints' => [
                'products' => '/product/api/products',
                'orders' => '/order/api/orders',
            ],
        ],
        'n11' => [
            'name' => 'n11',
            'code' => 'N11',
            'production_api_url' => 'https://api.n11.com/ws',
            'stage_api_url' => null,
            'use_stage' => false,
            'rate_limit' => [
                'requests_per_minute' => 60,
                'requests_per_hour' => 3600,
            ],
            'auth_type' => 'api_key',
            'timeout' => 30,
            'endpoints' => [
                'products' => '/ProductService',
                'orders' => '/OrderService',
            ],
        ],
        'amazon' => [
            'name' => 'Amazon TR',
            'code' => 'AMAZON',
            'production_api_url' => 'https://sellingpartnerapi-eu.amazon.com',
            'stage_api_url' => null,
            'use_stage' => false,
            'marketplace_id' => 'A33AVAJ2PDY3EV', // Turkey
            'rate_limit' => [
                'requests_per_minute' => 60,
                'requests_per_hour' => 3600,
            ],
            'auth_type' => 'oauth2',
            'timeout' => 30,
            'endpoints' => [
                'products' => '/catalog/2022-04-01/items',
                'orders' => '/orders/v0/orders',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Status Enums
    |--------------------------------------------------------------------------
    |
    | Standard order statuses across all marketplaces.
    | These will be mapped from marketplace-specific statuses.
    |
    */

    'order_statuses' => [
        'pending' => 'Pending',
        'approved' => 'Approved',
        'picking' => 'Picking',
        'invoiced' => 'Invoiced',
        'shipped' => 'Shipped',
        'delivered' => 'Delivered',
        'cancelled' => 'Cancelled',
        'returned' => 'Returned',
        'refunded' => 'Refunded',
    ],

    /*
    |--------------------------------------------------------------------------
    | Claim Status Enums
    |--------------------------------------------------------------------------
    |
    | Standard claim/return statuses across all marketplaces.
    |
    */

    'claim_statuses' => [
        'created' => 'Created',
        'supplier_approved' => 'Supplier Approved',
        'supplier_rejected' => 'Supplier Rejected',
        'resolved' => 'Resolved',
        'closed' => 'Closed',
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace Product Status Enums
    |--------------------------------------------------------------------------
    |
    | Product listing statuses on marketplaces.
    |
    */

    'product_statuses' => [
        'pending' => 'Pending Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'unlisted' => 'Unlisted',
        'suspended' => 'Suspended',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Type Enums
    |--------------------------------------------------------------------------
    |
    | Types of synchronization operations that can be performed.
    |
    */

    'sync_types' => [
        'full' => 'Full Sync',
        'incremental' => 'Incremental Sync',
        'manual' => 'Manual Sync',
        'scheduled' => 'Scheduled Sync',
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Type Enums
    |--------------------------------------------------------------------------
    |
    | Types of entities that can be synchronized.
    |
    */

    'entity_types' => [
        'product' => 'Product',
        'stock' => 'Stock',
        'price' => 'Price',
        'order' => 'Order',
        'claim' => 'Claim',
        'question' => 'Question',
        'category' => 'Category',
        'brand' => 'Brand',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sync Status Enums
    |--------------------------------------------------------------------------
    |
    | Possible statuses for synchronization operations.
    |
    */

    'sync_statuses' => [
        'success' => 'Success',
        'failed' => 'Failed',
        'partial' => 'Partial Success',
    ],

    /*
    |--------------------------------------------------------------------------
    | Commission Rates
    |--------------------------------------------------------------------------
    |
    | Default commission rates by marketplace.
    | These can be overridden per category or per product.
    |
    */

    'commission_rates' => [
        'trendyol' => [
            'default' => 15.0,
            'electronics' => 12.0,
            'fashion' => 18.0,
        ],
        'hepsiburada' => [
            'default' => 15.0,
        ],
        'n11' => [
            'default' => 15.0,
        ],
        'amazon' => [
            'default' => 15.0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shipping Configuration
    |--------------------------------------------------------------------------
    |
    | Default shipping costs and options.
    |
    */

    'shipping' => [
        'default_cost' => env('MARKETPLACE_DEFAULT_SHIPPING_COST', 0),
        'free_shipping_threshold' => env('MARKETPLACE_FREE_SHIPPING_THRESHOLD', 150),
        'weight_based_pricing' => [
            'enabled' => env('MARKETPLACE_WEIGHT_BASED_SHIPPING', true),
            'rates' => [
                ['max_weight' => 1, 'cost' => 10],
                ['max_weight' => 5, 'cost' => 15],
                ['max_weight' => 10, 'cost' => 25],
                ['max_weight' => null, 'cost' => 40], // null = over 10kg
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Profit Calculation
    |--------------------------------------------------------------------------
    |
    | Configuration for profit and cost calculations.
    |
    */

    'profit_calculation' => [
        'include_vat' => env('MARKETPLACE_PROFIT_INCLUDE_VAT', true),
        'include_shipping' => env('MARKETPLACE_PROFIT_INCLUDE_SHIPPING', true),
        'include_commission' => env('MARKETPLACE_PROFIT_INCLUDE_COMMISSION', true),
        'default_margin_target' => env('MARKETPLACE_DEFAULT_MARGIN_TARGET', 30), // percentage
    ],

];
