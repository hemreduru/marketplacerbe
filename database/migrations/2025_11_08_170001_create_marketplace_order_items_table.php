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
        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained()->onDelete('set null'); // Can be null if product deleted
            $table->foreignId('marketplace_product_id')->nullable()->constrained()->onDelete('set null');

            // Item identification from marketplace
            $table->string('marketplace_item_id')->nullable(); // Trendyol: orderLineId
            $table->string('marketplace_sku')->nullable();
            $table->string('barcode')->nullable()->index();

            // Product details (snapshot at order time)
            $table->string('product_name');
            $table->text('product_description')->nullable();
            $table->string('product_color')->nullable();
            $table->string('product_size')->nullable();

            // Quantity & Pricing
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 10, 2)->default(0); // Price per unit
            $table->decimal('total_price', 10, 2)->default(0); // unit_price * quantity
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('vat_amount', 10, 2)->default(0);
            $table->integer('vat_rate')->default(18); // Percentage
            $table->string('currency', 3)->default('TRY');

            // Commission (marketplace fee)
            $table->decimal('commission_amount', 10, 2)->nullable();
            $table->decimal('commission_rate', 5, 2)->nullable(); // Percentage

            // Item status
            $table->string('item_status')->nullable(); // packed, shipped, delivered, returned

            // Metadata
            $table->json('marketplace_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['marketplace_order_id', 'barcode']);
            $table->index(['product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
    }
};
