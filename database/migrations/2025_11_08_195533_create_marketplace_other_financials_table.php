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
        Schema::create('marketplace_other_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_id')->constrained()->cascadeOnDelete();
            $table->string('transaction_type', 50)->index(); // DeductionInvoices, FBA, WarehouseService, etc.
            $table->timestamp('transaction_date')->nullable()->index();
            $table->timestamp('receipt_date')->nullable();
            $table->string('order_number')->nullable()->index();
            $table->text('description')->nullable(); // Platform Hizmet Bedeli, Ceza, etc.
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debt', 15, 2)->default(0);
            $table->string('invoice_serial_number')->nullable()->index();
            $table->json('marketplace_data')->nullable();
            $table->timestamps();

            // Composite indexes with short names
            $table->index(['user_id', 'marketplace_id', 'transaction_date'], 'mof_user_mp_date_idx');
            $table->index(['transaction_type', 'transaction_date'], 'mof_type_date_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_other_financials');
    }
};
