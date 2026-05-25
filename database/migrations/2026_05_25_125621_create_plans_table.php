<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->decimal('price_monthly', 8, 2);
            $table->decimal('price_yearly', 8, 2)->nullable();
            $table->integer('marketplaces_limit');
            $table->integer('orders_limit');
            $table->integer('products_limit');
            $table->json('features');
            $table->integer('trial_days')->default(3);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('plans')->insert([
            [
                'name' => 'starter',
                'display_name' => 'Başlangıç',
                'description' => 'Tek pazaryeri ile e-ticarete giriş yapın.',
                'price_monthly' => 499.00,
                'price_yearly' => 4990.00,
                'marketplaces_limit' => 1,
                'orders_limit' => 100,
                'products_limit' => 500,
                'features' => json_encode([
                    'analytics' => false,
                    'claims' => false,
                    'repricing' => false,
                ]),
                'trial_days' => 3,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'growth',
                'display_name' => 'Büyüme',
                'description' => '3 pazaryeri ile işinizi büyütün, analitik ve şikayet yönetiminden yararlanın.',
                'price_monthly' => 1299.00,
                'price_yearly' => 12990.00,
                'marketplaces_limit' => 3,
                'orders_limit' => 1000,
                'products_limit' => 5000,
                'features' => json_encode([
                    'analytics' => true,
                    'claims' => true,
                    'repricing' => false,
                ]),
                'trial_days' => 3,
                'is_active' => true,
                'is_popular' => true,
                'sort_order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'pro',
                'display_name' => 'Profesyonel',
                'description' => 'Sınırsız pazaryeri, otomatik fiyatlandırma ve VIP destek ile maksimum büyüme.',
                'price_monthly' => 2499.00,
                'price_yearly' => 24990.00,
                'marketplaces_limit' => -1,
                'orders_limit' => -1,
                'products_limit' => -1,
                'features' => json_encode([
                    'analytics' => true,
                    'claims' => true,
                    'repricing' => true,
                ]),
                'trial_days' => 3,
                'is_active' => true,
                'is_popular' => false,
                'sort_order' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
