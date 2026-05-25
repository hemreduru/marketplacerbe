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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_marketplace_credential_id')
                ->constrained('user_marketplace_credentials')
                ->cascadeOnDelete();
            $table->string('remote_id');
            $table->text('question_text');
            $table->text('answer_text')->nullable();
            $table->string('status')->default('WAITING_FOR_ANSWER');
            $table->string('product_name')->nullable();
            $table->timestamp('question_date')->nullable();
            $table->timestamp('answered_date')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamps();

            $table->unique(['user_marketplace_credential_id', 'remote_id'], 'questions_credential_remote_unique');
            $table->index(['user_marketplace_credential_id', 'status'], 'questions_credential_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
