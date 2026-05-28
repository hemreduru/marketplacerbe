<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->nullable()->constrained('master_products')->nullOnDelete();
            $table->foreignId('user_marketplace_credential_id')->constrained()->cascadeOnDelete();

            $table->string('remote_product_id')->nullable();
            $table->string('remote_sku')->nullable();
            $table->string('remote_barcode')->nullable()->index();

            $table->string('listing_status', 32)->default('unknown');
            $table->decimal('listed_price', 15, 4)->default(0);
            $table->integer('listed_stock')->default(0);
            $table->string('listing_url', 512)->nullable();
            $table->string('category_path', 512)->nullable();
            $table->json('attributes_json')->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->string('sync_status', 32)->default('pending');
            $table->text('last_sync_error')->nullable();

            $table->timestamps();

            $table->index(['user_marketplace_credential_id', 'listing_status'], 'ml_credential_status_idx');
            $table->unique(['user_marketplace_credential_id', 'remote_product_id'], 'ml_credential_remote_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_listings');
    }
};
