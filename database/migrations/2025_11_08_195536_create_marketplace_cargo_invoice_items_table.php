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
        Schema::create('marketplace_cargo_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cargo_invoice_id')->constrained('marketplace_cargo_invoices')->cascadeOnDelete();
            $table->string('order_number')->index();
            $table->decimal('amount', 15, 2)->default(0);
            $table->text('description')->nullable();
            $table->json('marketplace_data')->nullable();
            $table->timestamps();

            // Indexes with short name
            $table->index(['cargo_invoice_id', 'order_number'], 'mci_invoice_order_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_cargo_invoice_items');
    }
};
