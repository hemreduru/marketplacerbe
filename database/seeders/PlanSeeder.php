<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Kanonik fiyatlandırma kademeleri (sipariş-hacmi bazlı, yıllık-öncelikli).
 * updateOrCreate ile idempotent — create_plans migration seed'iyle ve
 * add_free_plan_tier migration'ıyla çakışmaz.
 *
 * NOT (kod-dışı, flag): fiyatlar Melontik/PraPazar rakip teyidiyle KALİBRE
 * edilmeli; plan 14-30 gün trial öneriyor (mevcut sistem 3 gün — iş kararı,
 * mevcut değer korundu).
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->plans() as $plan) {
            $name = $plan['name'];
            unset($plan['name']);

            Plan::updateOrCreate(['name' => $name], $plan);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function plans(): array
    {
        return [
            [
                'name' => 'free',
                'display_name' => 'Ücretsiz',
                'description' => 'Tek ürün için kalıcı ücretsiz kâr özeti.',
                'price_monthly' => 0,
                'price_yearly' => 0,
                'marketplaces_limit' => 1,
                'orders_limit' => 30,
                'products_limit' => 1,
                'features' => ['analytics' => false, 'claims' => false, 'repricing' => false],
                'trial_days' => 0,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 0,
            ],
            [
                'name' => 'starter',
                'display_name' => 'Başlangıç',
                'description' => 'Tek pazaryeri ile e-ticarete giriş yapın.',
                'price_monthly' => 499,
                'price_yearly' => 4990,
                'marketplaces_limit' => 1,
                'orders_limit' => 100,
                'products_limit' => 500,
                'features' => ['analytics' => false, 'claims' => false, 'repricing' => false],
                'trial_days' => 3,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
            ],
            [
                'name' => 'growth',
                'display_name' => 'Büyüme',
                'description' => 'Çok pazaryeri, analitik ve şikayet yönetimiyle işinizi büyütün.',
                'price_monthly' => 1299,
                'price_yearly' => 12990,
                'marketplaces_limit' => 3,
                'orders_limit' => 1000,
                'products_limit' => 5000,
                'features' => ['analytics' => true, 'claims' => true, 'repricing' => false],
                'trial_days' => 3,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'pro',
                'display_name' => 'Profesyonel',
                'description' => 'Sınırsız hacim, repricer ve para-iade/dispute otomasyonu + öncelikli destek.',
                'price_monthly' => 2499,
                'price_yearly' => 24990,
                'marketplaces_limit' => -1,
                'orders_limit' => -1,
                'products_limit' => -1,
                'features' => ['analytics' => true, 'claims' => true, 'repricing' => true],
                'trial_days' => 3,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
            ],
        ];
    }
}
