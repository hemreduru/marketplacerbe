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
        Schema::create('marketplace_questions', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('marketplace_id')->constrained('marketplaces')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('set null');
            $table->foreignId('marketplace_product_id')->nullable()->constrained('marketplace_products')->onDelete('set null');

            // Marketplace question identifiers
            $table->string('marketplace_question_id')->index();
            $table->string('marketplace_product_id_value')->nullable();

            // Question details
            $table->text('question_text');
            $table->text('answer_text')->nullable();
            $table->string('question_status', 50)->index(); // Pending, Answered, Rejected

            // Customer information
            $table->string('customer_name')->nullable();
            $table->boolean('show_customer_name')->default(true);

            // Product information (snapshot)
            $table->string('product_name')->nullable();
            $table->string('product_sku')->nullable();

            // Timestamps
            $table->timestamp('question_date')->nullable();
            $table->timestamp('answered_at')->nullable();

            // Additional data
            $table->json('marketplace_raw_data')->nullable();
            $table->text('internal_notes')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['marketplace_id', 'marketplace_question_id'], 'mq_marketplace_question_unique');
            $table->index(['user_id', 'question_status'], 'mq_user_status_index');
            $table->index(['marketplace_id', 'question_date'], 'mq_marketplace_date_index');
            $table->index('marketplace_product_id_value', 'mq_product_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_questions');
    }
};
