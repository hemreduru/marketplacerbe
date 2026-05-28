<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cargo_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tracking_number', 100)->nullable();
            $table->string('label_url', 500)->nullable();
            $table->string('label_format', 16)->nullable();
            $table->string('status', 32)->default('created');
            $table->integer('package_count')->default(1);
            $table->decimal('total_weight_kg', 8, 3)->nullable();
            $table->decimal('total_desi', 8, 3)->nullable();
            $table->json('sender_address')->nullable();
            $table->json('receiver_address')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('tracking_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
