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
        Schema::create('marketplaces', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Trendyol, Hepsiburada, n11, Pazarama
            $table->string('slug')->unique(); // trendyol, hepsiburada, n11
            $table->string('code')->unique(); // TRENDYOL, HEPSIBURADA, N11
            $table->string('api_base_url')->nullable();
            $table->string('logo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->json('config')->nullable(); // Marketplace-specific settings
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplaces');
    }
};
