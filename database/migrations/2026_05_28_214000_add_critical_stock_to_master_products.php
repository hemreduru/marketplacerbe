<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->integer('critical_stock_threshold')->default(0)->after('stock_buffer_value');
            $table->boolean('stock_alert_enabled')->default(false)->after('critical_stock_threshold');
        });
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropColumn(['critical_stock_threshold', 'stock_alert_enabled']);
        });
    }
};
