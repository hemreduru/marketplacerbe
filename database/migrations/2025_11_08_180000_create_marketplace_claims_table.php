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
        Schema::create('marketplace_claims', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('marketplace_id')->constrained('marketplaces')->onDelete('cascade');
            $table->foreignId('marketplace_order_id')->nullable()->constrained('marketplace_orders')->onDelete('set null');

            // Marketplace claim identifiers
            $table->string('marketplace_claim_id')->index(); // Trendyol claimId
            $table->string('marketplace_order_id_value')->nullable(); // Original order ID from marketplace
            $table->string('package_number')->nullable();

            // Claim details
            $table->string('claim_type', 50)->index(); // Return, Refund, Damage, etc.
            $table->string('claim_status', 50)->index(); // Created, Approved, Rejected, Completed, Cancelled
            $table->text('claim_reason')->nullable(); // Why customer returned
            $table->text('customer_note')->nullable(); // Customer's explanation
            $table->text('seller_note')->nullable(); // Seller's response note

            // Customer information
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();

            // Financial details
            $table->decimal('claim_amount', 10, 2)->default(0); // Total claim amount
            $table->decimal('approved_amount', 10, 2)->default(0); // Approved refund amount
            $table->string('currency', 3)->default('TRY');

            // Return shipping
            $table->string('return_tracking_number')->nullable();
            $table->string('return_carrier')->nullable();
            $table->timestamp('return_shipped_at')->nullable();
            $table->timestamp('return_received_at')->nullable();

            // Claim lifecycle dates
            $table->timestamp('claim_date')->nullable(); // When claim was created
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            // Additional data
            $table->json('marketplace_raw_data')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['marketplace_id', 'marketplace_claim_id']);
            $table->index(['user_id', 'claim_status']);
            $table->index(['marketplace_id', 'claim_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_claims');
    }
};
