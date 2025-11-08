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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique(); // Unique stock code
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('barcode')->nullable()->index();
            $table->integer('stock_quantity')->default(0);
            $table->decimal('base_price', 10, 2)->nullable(); // Without VAT
            $table->decimal('sale_price', 10, 2)->nullable(); // With VAT
            $table->integer('vat_rate')->default(18); // Percentage
            $table->string('currency', 3)->default('TRY');
            $table->decimal('weight', 8, 2)->nullable();
            $table->decimal('dimensional_weight', 8, 2)->nullable();
            $table->json('images')->nullable(); // Array of image URLs
            $table->json('attributes')->nullable(); // General attributes
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
