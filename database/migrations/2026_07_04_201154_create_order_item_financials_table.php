<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Kalem bazlı kâr defteri — hibrit tahmin/settlement modeli.
 *
 * Her order_item için tek satır: önce tahmin (estimate) yazılır, settlement
 * verisi geldikçe SettlementReconciler gerçek tutarlarla günceller.
 * İlk tahmin snapshot'ı (estimated_*) mutabakat sapması ölçümü için saklanır.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_item_financials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_marketplace_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('master_product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('marketplace_code', 32);
            $table->date('order_date');

            // Etkin (güncel en iyi) kalemler — bcmath decimal(15,4)
            $table->decimal('net_revenue', 15, 4)->default(0);
            $table->decimal('cogs', 15, 4)->default(0);
            $table->decimal('commission', 15, 4)->default(0);
            $table->decimal('service_fee', 15, 4)->default(0);
            $table->decimal('shipping', 15, 4)->default(0);
            $table->decimal('stopaj', 15, 4)->default(0);
            $table->decimal('ad_cost', 15, 4)->default(0);
            $table->decimal('return_cost', 15, 4)->default(0);
            $table->decimal('packaging', 15, 4)->default(0);
            $table->decimal('net_profit', 15, 4)->default(0);
            $table->decimal('margin', 8, 4)->default(0);

            // Mutabakat durumu
            $table->string('source', 16)->default('estimate');
            $table->string('reconciliation_status', 24)->default('estimated');
            $table->decimal('estimated_net_profit', 15, 4)->nullable();
            $table->json('estimate_breakdown')->nullable();
            $table->json('actual_breakdown')->nullable();
            $table->json('component_sources')->nullable();
            $table->timestamp('estimated_at')->nullable();
            $table->timestamp('reconciled_at')->nullable();
            $table->timestamps();

            $table->index(['user_marketplace_credential_id', 'order_date'], 'oif_credential_date_idx');
            $table->index(['master_product_id', 'order_date'], 'oif_master_date_idx');
            $table->index('reconciliation_status', 'oif_recon_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_item_financials');
    }
};
