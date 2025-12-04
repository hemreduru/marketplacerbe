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
            $table->foreignId('user_marketplace_credential_id')->constrained()->onDelete('cascade');
            $table->string('remote_id')->nullable()->index(); // ID on the marketplace
            $table->string('barcode')->nullable()->index();
            $table->string('sku')->nullable()->index();
            $table->string('title');
            $table->string('brand')->nullable()->index();
            $table->string('category_name')->nullable()->index();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('list_price', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('currency')->default('TRY');
            $table->string('status')->default('active');
            $table->json('images')->nullable();
            $table->json('attributes')->nullable(); // For dynamic attributes
            $table->text('description')->nullable();
            $table->string('product_url')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicates for the same credential
            $table->unique(['user_marketplace_credential_id', 'remote_id']);
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
