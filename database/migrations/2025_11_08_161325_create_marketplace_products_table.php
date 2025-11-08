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
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Trendyol-specific fields from API response
            $table->string('marketplace_product_id')->nullable(); // Trendyol's internal ID
            $table->string('marketplace_sku')->nullable(); // barcode from Trendyol
            $table->string('stock_code')->nullable(); // stockCode from Trendyol
            $table->string('product_code')->nullable(); // productCode from Trendyol
            $table->string('product_main_id')->nullable(); // productMainId from Trendyol
            $table->string('platform_listing_id')->nullable(); // platformListingId
            $table->string('stock_id')->nullable(); // stockId from Trendyol
            $table->string('batch_request_id')->nullable(); // batchRequestId

            // Category and brand
            $table->string('marketplace_category_id')->nullable();
            $table->string('marketplace_category_name')->nullable();
            $table->string('marketplace_brand_id')->nullable();
            $table->string('marketplace_brand_name')->nullable();

            // Product info
            $table->string('marketplace_title')->nullable();
            $table->text('marketplace_description')->nullable();
            $table->decimal('marketplace_list_price', 10, 2)->nullable();
            $table->decimal('marketplace_sale_price', 10, 2)->nullable();
            $table->integer('marketplace_stock')->default(0);

            // Status and approval
            $table->boolean('approved')->default(false);
            $table->enum('marketplace_status', ['waiting', 'approved', 'rejected', 'active', 'passive'])->default('waiting');
            $table->string('marketplace_url')->nullable();

            // Additional Trendyol fields
            $table->string('gender')->nullable(); // M, F, U
            $table->string('color')->nullable();
            $table->string('stock_unit_type')->nullable(); // Adet, Kg, etc.
            $table->string('location_based_delivery')->nullable(); // ENABLED, DISABLED
            $table->string('lot_number')->nullable();
            $table->json('delivery_option')->nullable(); // deliveryDuration, fastDeliveryType
            $table->json('variant_attributes')->nullable(); // Renk, Beden, etc.

            // Sync tracking
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_errors')->nullable();
            $table->json('marketplace_data')->nullable(); // Full API response

            $table->timestamps();

            $table->unique(['product_id', 'marketplace_id']);
            $table->index('marketplace_product_id');
            $table->index('marketplace_sku');
            $table->index('stock_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
