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
        Schema::create('marketplace_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_id')->constrained('marketplaces')->onDelete('cascade');
            $table->string('marketplace_category_id')->index(); // Marketplace's category ID
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('marketplace_categories')->onDelete('cascade');
            $table->text('full_path')->nullable(); // Full category path (e.g., "Electronics > Phones > Smartphones")
            $table->integer('level')->default(0); // Hierarchy level (0 = root)
            $table->boolean('is_leaf')->default(false); // Is this a leaf category (can have products)
            $table->json('attributes')->nullable(); // Category-specific attributes
            $table->json('marketplace_raw_data')->nullable();
            $table->timestamps();

            // Composite unique index
            $table->unique(['marketplace_id', 'marketplace_category_id'], 'mc_marketplace_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_categories');
    }
};
