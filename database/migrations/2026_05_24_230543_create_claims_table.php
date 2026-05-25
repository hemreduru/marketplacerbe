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
        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_marketplace_credential_id')
                ->constrained('user_marketplace_credentials')
                ->cascadeOnDelete();
            $table->string('remote_id');
            $table->string('order_number')->nullable();
            $table->string('status')->nullable();
            $table->string('customer_name')->nullable();
            $table->unsignedInteger('item_count')->default(0);
            $table->timestamp('claim_date')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['user_marketplace_credential_id', 'remote_id'], 'claims_credential_remote_unique');
            $table->index(['user_marketplace_credential_id', 'status'], 'claims_credential_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claims');
    }
};
