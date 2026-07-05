<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Abonelik ödeme denetim kaydı — her ödeme denemesi (pending/success/failed)
 * burada tutulur. iyzico conversation_id ile callback'e korelasyon; para
 * decimal(15,4). Fatura (Paraşüt) sonraki adımda bu kayda bağlanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('billing_period')->default('monthly');
            $table->decimal('amount', 15, 4);
            $table->string('currency', 3)->default('TRY');
            $table->string('status')->default('pending'); // pending | success | failed
            $table->string('conversation_id')->unique();
            $table->string('payment_id')->nullable();
            $table->string('error_message')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
