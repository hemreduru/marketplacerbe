<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MarketplaceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $marketplaces = [
            [
                'name' => 'Trendyol',
                'slug' => 'trendyol',
                'code' => 'TRENDYOL',
                'api_base_url' => 'https://api.trendyol.com/sapigw',
                'logo' => null,
                'is_active' => true,
                'config' => json_encode([
                    'stage_api_url' => 'https://stageapi.trendyol.com/stageapi',
                    'user_agent_format' => '{supplier_id} - {integrator_name}',
                    'auth_type' => 'basic',
                    'rate_limit' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 3600,
                    ],
                    'endpoints' => [
                        'products' => '/suppliers/{supplierId}/products',
                        'product_detail' => '/suppliers/{supplierId}/products',
                        'product_batch' => '/suppliers/{supplierId}/products/batch-requests/{batchRequestId}',
                        'orders' => '/suppliers/{supplierId}/orders',
                        'claims' => '/suppliers/{supplierId}/claims',
                        'questions' => '/suppliers/{supplierId}/questions',
                        'brands' => '/brands',
                        'categories' => '/product-categories',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hepsiburada',
                'slug' => 'hepsiburada',
                'code' => 'HEPSIBURADA',
                'api_base_url' => 'https://mpop-sit.hepsiburada.com',
                'logo' => null,
                'is_active' => false,
                'config' => json_encode([
                    'production_api_url' => 'https://mpop.hepsiburada.com',
                    'auth_type' => 'bearer',
                    'rate_limit' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 3600,
                    ],
                    'endpoints' => [
                        'products' => '/product/api/products',
                        'orders' => '/order/api/orders',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'n11',
                'slug' => 'n11',
                'code' => 'N11',
                'api_base_url' => 'https://api.n11.com/ws',
                'logo' => null,
                'is_active' => false,
                'config' => json_encode([
                    'auth_type' => 'api_key',
                    'rate_limit' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 3600,
                    ],
                    'endpoints' => [
                        'products' => '/ProductService',
                        'orders' => '/OrderService',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Amazon TR',
                'slug' => 'amazon-tr',
                'code' => 'AMAZON',
                'api_base_url' => 'https://sellingpartnerapi-eu.amazon.com',
                'logo' => null,
                'is_active' => false,
                'config' => json_encode([
                    'auth_type' => 'oauth2',
                    'marketplace_id' => 'A33AVAJ2PDY3EV', // Turkey marketplace ID
                    'rate_limit' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 3600,
                    ],
                    'endpoints' => [
                        'products' => '/catalog/2022-04-01/items',
                        'orders' => '/orders/v0/orders',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        \DB::table('marketplaces')->insert($marketplaces);
    }
}
