<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dashboard'ın COGS dahil GERÇEK net kârı gösterebilmesi için günlük özete
 * yeni maliyet kolonları. Legacy `net_profit` (pazaryeri-kesintili kâr)
 * geriye uyumluluk için DOKUNULMADAN kalır; `true_net_profit` eklenir:
 *
 *   true_net_profit = gross_sales − commission − shipping_cost
 *                   − platform_expense − other_expense
 *                   − cogs − stopaj − ad_cost − return_cost
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_daily_summaries', function (Blueprint $table) {
            $table->decimal('cogs', 15, 4)->default(0)->after('other_expense');
            $table->decimal('stopaj', 15, 4)->default(0)->after('cogs');
            $table->decimal('ad_cost', 15, 4)->default(0)->after('stopaj');
            $table->decimal('return_cost', 15, 4)->default(0)->after('ad_cost');
            $table->decimal('true_net_profit', 15, 4)->default(0)->after('net_profit');
        });
    }

    public function down(): void
    {
        Schema::table('financial_daily_summaries', function (Blueprint $table) {
            $table->dropColumn(['cogs', 'stopaj', 'ad_cost', 'return_cost', 'true_net_profit']);
        });
    }
};
