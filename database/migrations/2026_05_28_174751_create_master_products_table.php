<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('brand')->nullable()->index();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable()->index();

            // Para alanları: decimal(15,4) — float yasak (Spec Bölüm 12.5)
            $table->decimal('cost_price', 15, 4)->default(0);
            $table->decimal('cost_price_vat_rate', 5, 2)->default(20.00);
            $table->decimal('vat_rate', 5, 2)->default(20.00);

            $table->unsignedInteger('weight_g')->default(0);
            $table->decimal('desi', 8, 2)->default(0);
            $table->decimal('packaging_cost', 15, 4)->default(0);

            // Stok ve fiyat projeksiyon kolonları (event'ler tek truth source)
            $table->integer('current_stock')->default(0);
            $table->decimal('current_price', 15, 4)->default(0);

            $table->string('pricing_strategy', 32)->default('manual');
            $table->string('stock_buffer_strategy', 32)->default('none');
            $table->integer('stock_buffer_value')->default(0);

            // Optimistic lock — projector race condition önler
            $table->unsignedBigInteger('version')->default(0);

            $table->json('marketplace_specific_attributes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'sku']);
            $table->index(['user_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_products');
    }
};
