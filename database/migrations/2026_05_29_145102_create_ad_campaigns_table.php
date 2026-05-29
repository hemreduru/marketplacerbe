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
        Schema::create('ad_campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_marketplace_credential_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace_code', 32);
            $table->string('remote_campaign_id');
            $table->string('name');
            $table->string('status', 32)->default('active');
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamps();

            $table->unique(['user_marketplace_credential_id', 'remote_campaign_id'], 'ad_campaigns_credential_remote_uq');
            $table->index(['marketplace_code', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ad_campaigns');
    }
};
