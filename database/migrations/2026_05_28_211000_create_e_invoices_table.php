<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('e_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider', 32);
            $table->string('invoice_uuid', 100)->nullable()->unique();
            $table->string('e_invoice_number', 50)->nullable();
            $table->string('e_archive_number', 50)->nullable();
            $table->string('status', 32)->default('draft');
            $table->decimal('subtotal', 15, 4)->default(0);
            $table->decimal('total_vat', 15, 4)->default(0);
            $table->decimal('total_amount', 15, 4)->default(0);
            $table->string('pdf_url', 500)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('e_invoices');
    }
};
