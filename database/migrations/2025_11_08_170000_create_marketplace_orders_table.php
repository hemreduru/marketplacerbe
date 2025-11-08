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
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');

            // Order identification
            $table->string('marketplace_order_id')->index(); // Trendyol: orderNumber
            $table->string('marketplace_order_number')->nullable(); // Human-readable order number
            $table->string('package_number')->nullable()->index(); // Trendyol: packageNumber (unique per shipment)

            // Order details
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            // Status
            $table->string('order_status')->index(); // created, picking, invoiced, shipped, delivered, cancelled
            $table->string('shipment_status')->nullable(); // Trendyol-specific shipment status

            // Financial
            $table->decimal('total_price', 10, 2)->default(0);
            $table->decimal('gross_amount', 10, 2)->default(0); // Before discounts/taxes
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->string('currency', 3)->default('TRY');

            // Shipping
            $table->string('shipping_company')->nullable();
            $table->string('tracking_number')->nullable()->index();
            $table->string('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_district')->nullable();
            $table->string('shipping_postal_code')->nullable();

            // Billing
            $table->string('billing_address')->nullable();
            $table->string('billing_city')->nullable();
            $table->string('billing_district')->nullable();
            $table->string('billing_postal_code')->nullable();

            // Invoice
            $table->string('invoice_number')->nullable()->index();
            $table->string('invoice_link')->nullable();
            $table->timestamp('invoiced_at')->nullable();

            // Timestamps
            $table->timestamp('order_date')->nullable(); // When customer placed order
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('last_sync_at')->nullable();

            // Metadata
            $table->json('marketplace_data')->nullable(); // Full API response
            $table->json('notes')->nullable(); // Internal notes

            $table->timestamps();

            // Indexes
            $table->unique(['marketplace_id', 'marketplace_order_id']);
            $table->index(['user_id', 'order_status']);
            $table->index(['marketplace_id', 'order_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
    }
};
