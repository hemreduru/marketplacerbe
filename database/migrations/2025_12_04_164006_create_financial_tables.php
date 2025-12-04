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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_marketplace_credential_id')->constrained('user_marketplace_credentials')->onDelete('cascade');
            $table->string('transaction_type'); // Sale, Return, Deduction, etc.
            $table->dateTime('transaction_date');
            $table->string('order_number')->nullable();
            $table->decimal('amount', 10, 2)->default(0); // Credit or Debt depending on context, or net effect
            $table->decimal('commission', 10, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // Store extra fields like barcode, package_id, raw response
            $table->timestamps();

            // Indexes for faster querying
            $table->index(['user_marketplace_credential_id', 'transaction_date'], 'ft_credential_date_index');
            $table->index('order_number');
        });

        Schema::create('financial_daily_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_marketplace_credential_id')->constrained('user_marketplace_credentials')->onDelete('cascade');
            $table->date('date');
            $table->decimal('gross_sales', 10, 2)->default(0);
            $table->decimal('commission', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('platform_expense', 10, 2)->default(0);
            $table->decimal('other_expense', 10, 2)->default(0); // Penalty, Intl, etc.
            $table->decimal('net_profit', 10, 2)->default(0);
            $table->integer('order_count')->default(0);
            $table->integer('item_count')->default(0);
            $table->timestamps();

            // Unique constraint to prevent duplicate summaries for the same day/credential
            $table->unique(['user_marketplace_credential_id', 'date'], 'daily_summary_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_daily_summaries');
        Schema::dropIfExists('financial_transactions');
    }
};
