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
        Schema::create('product_additional_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_id')->nullable()->constrained()->onDelete('cascade');

            // Expense details
            $table->string('expense_type', 50); // packaging, advertising, storage, shipping_material, extra_service, other
            $table->string('title', 100);
            $table->text('description')->nullable();
            $table->decimal('amount', 10, 2); // Expense amount
            $table->string('currency', 3)->default('TRY');
            $table->date('expense_date');

            // Allocation method
            $table->enum('allocation_type', ['per_product', 'per_marketplace', 'global'])->default('per_product');
            // per_product: specific product expense
            // per_marketplace: all products in that marketplace
            // global: all products in all marketplaces

            // Optional: Receipt/Invoice tracking
            $table->string('receipt_number')->nullable();
            $table->json('attachments')->nullable(); // File paths for receipts

            // Metadata
            $table->json('metadata')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_period')->nullable(); // monthly, quarterly, yearly
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'product_id', 'expense_date'], 'pae_user_prod_date');
            $table->index(['user_id', 'marketplace_id', 'expense_date'], 'pae_user_mp_date');
            $table->index(['expense_type', 'allocation_type'], 'pae_type_alloc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_additional_expenses');
    }
};
