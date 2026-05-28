<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cargo_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_provider_id')->constrained()->cascadeOnDelete();
            $table->text('username');
            $table->text('password');
            $table->string('customer_code', 100)->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('ip_whitelisted_at')->nullable();
            $table->json('additional_config')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'cargo_provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cargo_credentials');
    }
};
