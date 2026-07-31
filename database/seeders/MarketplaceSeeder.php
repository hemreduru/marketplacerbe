<?php

namespace Database\Seeders;

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
                'api_base_url' => 'https://apigw.trendyol.com',
                'logo' => null,
                'is_active' => true,
                'config' => json_encode([
                    'stage_api_url' => 'https://stageapigw.trendyol.com',
                    'user_agent_format' => '{supplier_id} - {integrator_name}',
                    'auth_type' => 'basic',
                    'rate_limit' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 3600,
                    ],
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
                'name' => 'Pazarama',
                'slug' => 'pazarama',
                'code' => 'PAZARAMA',
                'api_base_url' => 'https://isortagimapi.pazarama.com',
                'logo' => null,
                'is_active' => false,
                'config' => json_encode([
                    'auth_type' => 'oauth_client_credentials',
                    'rate_limit' => [
                        'requests_per_minute' => 60,
                        'requests_per_hour' => 3600,
                    ],
                    'endpoints' => [
                        'products' => '/product/products',
                        'orders' => '/order/orders',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Idempotent: slug'a göre upsert — yeniden çalıştırınca kopya oluşturmaz.
        foreach ($marketplaces as $marketplace) {
            \DB::table('marketplaces')->updateOrInsert(
                ['slug' => $marketplace['slug']],
                $marketplace
            );
        }
    }
}
