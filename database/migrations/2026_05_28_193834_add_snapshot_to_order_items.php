<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->nullable()->after('price');
            $table->decimal('commission_amount', 10, 4)->nullable()->after('commission_rate');
            $table->decimal('shipping_cost', 10, 4)->nullable()->after('commission_amount');
            $table->foreignId('master_product_id')->nullable()->after('shipping_cost')->constrained('master_products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['master_product_id']);
            $table->dropColumn(['master_product_id', 'shipping_cost', 'commission_amount', 'commission_rate']);
        });
    }
};
