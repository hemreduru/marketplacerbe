<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Webhook URL'lerinde credential lookup için unique identifier.
     * Her credential'a otomatik UUID üretilir.
     */
    public function up(): void
    {
        Schema::table('user_marketplace_credentials', function (Blueprint $table) {
            $table->uuid('webhook_uuid')->unique()->nullable()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('user_marketplace_credentials', function (Blueprint $table) {
            $table->dropColumn('webhook_uuid');
        });
    }
};
