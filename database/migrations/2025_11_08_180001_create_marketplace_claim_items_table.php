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
        Schema::create('marketplace_claim_items', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('marketplace_claim_id')->constrained('marketplace_claims')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('marketplace_product_id')->nullable()->constrained('marketplace_products')->onDelete('set null');
            $table->foreignId('marketplace_order_item_id')->nullable()->constrained('marketplace_order_items')->onDelete('set null');

            // Marketplace item identifiers
            $table->string('marketplace_item_id')->nullable();
            $table->string('barcode')->nullable()->index();

            // Product snapshot (at time of claim)
            $table->string('product_name');
            $table->string('product_sku')->nullable();
            $table->string('variant_info')->nullable(); // Size, color, etc.

            // Quantities
            $table->integer('quantity_claimed')->default(1); // How many returned
            $table->integer('quantity_approved')->default(0); // How many approved for refund

            // Financial details
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_amount', 10, 2)->default(0); // unit_price * quantity_claimed
            $table->decimal('refund_amount', 10, 2)->default(0); // Actual refund given
            $table->string('currency', 3)->default('TRY');

            // Item condition
            $table->string('item_condition', 50)->nullable(); // New, Used, Damaged
            $table->text('item_condition_note')->nullable();

            // Claim reason for this specific item
            $table->string('claim_reason', 100)->nullable(); // Defective, Wrong Item, Size Issue, etc.
            $table->text('customer_complaint')->nullable();

            // Resolution
            $table->string('resolution', 50)->nullable(); // Refunded, Replaced, Rejected
            $table->text('resolution_note')->nullable();

            // Additional data
            $table->json('marketplace_raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('marketplace_claim_id');
            $table->index('product_id');
            $table->index('marketplace_product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_claim_items');
    }
};
