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
        Schema::create('marketplace_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->string('sync_type'); // product_create, product_update, product_pull, stock_sync, etc.
            $table->string('entity_type'); // product, order, claim, question
            $table->unsignedBigInteger('entity_id')->nullable(); // Related entity ID
            $table->enum('status', ['success', 'failed', 'partial'])->default('success');
            $table->json('request_data')->nullable(); // Data sent to marketplace
            $table->json('response_data')->nullable(); // Response from marketplace
            $table->text('error_message')->nullable();
            $table->integer('duration_ms')->nullable(); // Request duration in milliseconds
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'marketplace_id', 'created_at']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_sync_logs');
    }
};
