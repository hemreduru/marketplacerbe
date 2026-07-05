<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Onboarding sihirbazı ilerleme takibi — tamamlanan adım anahtarları (json).
 * Adımlar gerçek veriden türetilir (abonelik/credential/sync); bu kolon
 * kalıcılaştırılmış görünürlük (admin paneli + tamamlanma) sağlar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('onboarding_completed_steps')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('onboarding_completed_steps');
        });
    }
};
