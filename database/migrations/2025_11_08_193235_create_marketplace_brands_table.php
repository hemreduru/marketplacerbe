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
        Schema::create('marketplace_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->onDelete('cascade');
            $table->string('marketplace_brand_id')->index(); // Marketplace's brand ID
            $table->string('name');
            $table->json('marketplace_raw_data')->nullable();
            $table->timestamps();

            // Composite unique index
            $table->unique(['marketplace_id', 'marketplace_brand_id'], 'mb_marketplace_brand_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_brands');
    }
};
