<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('marketplace_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace_order_id')->nullable()->index();
            $table->string('transaction_type', 50)->index(); // Sale, Return, Discount, Commission, etc.
            $table->timestamp('transaction_date')->nullable()->index();
            $table->timestamp('payment_date')->nullable();
            $table->string('order_number')->nullable()->index();
            $table->string('package_id')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->decimal('credit', 15, 2)->default(0); // Alacak
            $table->decimal('debt', 15, 2)->default(0);   // Borç
            $table->decimal('commission_amount', 15, 2)->default(0);
            $table->decimal('seller_revenue', 15, 2)->default(0);
            $table->string('store_id')->nullable();
            $table->string('payment_order_id')->nullable();
            $table->json('marketplace_data')->nullable();
            $table->timestamps();

            // Composite indexes with short names
            $table->index(['user_id', 'marketplace_id', 'transaction_date'], 'ms_user_mp_date_idx');
            $table->index(['transaction_type', 'transaction_date'], 'ms_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_settlements');
    }
};
