<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('status', 32);
            $table->string('location', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->timestamp('occurred_at');
            $table->string('source', 32)->default('polling');
            $table->string('external_reference', 255)->nullable();
            $table->timestamps();

            $table->unique(['shipment_id', 'status', 'source', 'external_reference'], 'shipment_events_dedup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
    }
};
