<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sipariş teslimat adres kolonları (şehir/ülke).
 *
 * Not: Canlı MySQL'de bu kolonlar migration dışında elle eklenmişti (schema drift);
 * bu migration drift'i resmileştirir. hasColumn guard'ı ile zaten var olan ortamda
 * (MySQL) atlar, temiz kurulumda (SQLite/test, fresh deploy) ekler.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (! Schema::hasColumn('orders', 'shipping_city')) {
                $table->string('shipping_city')->nullable()->after('customer_email');
            }
            if (! Schema::hasColumn('orders', 'shipping_country')) {
                $table->string('shipping_country')->nullable()->after('shipping_city');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            foreach (['shipping_city', 'shipping_country'] as $column) {
                if (Schema::hasColumn('orders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
