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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_last_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->decimal('total_amount', 10, 2)->default(0);
            $table->string('currency_code')->default('TRY');
            $table->string('status')->default('Created');
            $table->string('shipment_package_status')->nullable();
            $table->timestamp('order_date')->nullable();
            $table->string('cargo_tracking_number')->nullable();
            $table->string('cargo_provider_name')->nullable();
            $table->json('raw_data')->nullable(); // Store full API response for reference
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
