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
            $table->foreignId('user_marketplace_credential_id')
                ->constrained('user_marketplace_credentials')
                ->cascadeOnDelete();
            $table->string('entity_type'); // product, order, finance, question, claim
            $table->string('status')->default('running'); // running, success, failed
            $table->unsignedInteger('created_count')->default(0);
            $table->unsignedInteger('updated_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['user_marketplace_credential_id', 'entity_type'], 'sync_log_credential_entity_index');
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
