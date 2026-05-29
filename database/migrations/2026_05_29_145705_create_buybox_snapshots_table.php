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
        Schema::create('buybox_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_listing_id')->constrained()->cascadeOnDelete();
            $table->boolean('has_buybox')->default(false);
            $table->decimal('our_price', 15, 4)->default(0);
            $table->decimal('competitor_price', 15, 4)->nullable();
            $table->string('competitor_seller')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();

            $table->index(['marketplace_listing_id', 'checked_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('buybox_snapshots');
    }
};
