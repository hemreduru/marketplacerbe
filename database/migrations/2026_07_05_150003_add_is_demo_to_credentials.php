<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demo/sandbox credential işareti — satıcı gerçek anahtar girmeden ürünü
 * denesin (Plan WS-3). Demo credential'lar gerçek API'ye karşı sync EDİLMEZ;
 * yalnızca seed'li demo veriyi taşır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_marketplace_credentials', function (Blueprint $table) {
            $table->boolean('is_demo')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('user_marketplace_credentials', function (Blueprint $table) {
            $table->dropColumn('is_demo');
        });
    }
};
