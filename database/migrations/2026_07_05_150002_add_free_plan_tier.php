<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kalıcı ücretsiz tier — tek ürün / read-only kâr özeti. Ücretsiz "kâr
 * hesaplama" calculator rakiplerine karşı signup kaması (Plan WS-5).
 * idempotent (updateOrInsert) — migration seed'iyle/PlanSeeder ile çakışmaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('plans')->updateOrInsert(
            ['name' => 'free'],
            [
                'display_name' => 'Ücretsiz',
                'description' => 'Tek ürün için kalıcı ücretsiz kâr özeti — Cirotik\'i risksiz deneyin.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'marketplaces_limit' => 1,
                'orders_limit' => 30,
                'products_limit' => 1,
                'features' => json_encode(['analytics' => false, 'claims' => false, 'repricing' => false]),
                'trial_days' => 0,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('plans')->where('name', 'free')->delete();
    }
};
