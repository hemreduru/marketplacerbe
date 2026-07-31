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
        Schema::create('user_marketplace_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_id')->constrained()->onDelete('cascade');
            $table->string('api_key'); // or supplier_id, seller_id
            $table->string('api_secret');
            $table->longText('additional_credentials')->nullable(); // şifreli blob (JSON değil) — encrypt migration ile uyumlu
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'marketplace_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_marketplace_credentials');
    }
};
