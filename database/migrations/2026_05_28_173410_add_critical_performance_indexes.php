<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Faz 0 PR #0.9 — Sık sorgulanan alanlara compound index ekler.
 *
 * Mevcut şema kullanımı:
 *   - Dashboard ve raporlar: `Order::where('user_id', $id)->where('order_date', ...)` → orders(user_id, order_date)
 *   - Sipariş kalemi → SKU bazlı kâr raporu: `OrderItem::where('merchant_sku', ...)->whereHas('order', ...)` → order_items(merchant_sku)
 *   - Aylık KDV / settlement: `FinancialTransaction::whereBetween('transaction_date', ...)` → financial_transactions(transaction_date)
 *
 * products tablosunda `barcode` ve `sku` index'leri zaten mevcut (2025_12_04_190410).
 * financial_transactions(user_marketplace_credential_id, transaction_date) compound var; tek başına
 * `transaction_date` filtresi (cross-credential admin sorguları) için ayrı index ekleniyor.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['user_id', 'order_date'], 'orders_user_date_idx');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('merchant_sku', 'order_items_merchant_sku_idx');
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->index('transaction_date', 'ft_transaction_date_idx');
        });
    }

    public function down(): void
    {
        // MySQL compound index'i (user_id, order_date) FK constraint için kullanır.
        // Drop'tan önce FK'yı geçici kaldırıp tekrar oluşturuyoruz ki FK kendi index'ini alsın.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropIndex('orders_user_date_idx');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_merchant_sku_idx');
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropIndex('ft_transaction_date_idx');
        });
    }
};
