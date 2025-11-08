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
        Schema::create('marketplace_cargo_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_serial_number')->unique();
            $table->date('invoice_date')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->string('status', 50)->default('active');
            $table->json('marketplace_data')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'marketplace_id']);
            $table->index('invoice_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_cargo_invoices');
    }
};
