<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_listing_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_type', 32);
            $table->string('source', 32);
            $table->string('source_reference')->nullable();

            $table->integer('quantity_delta');

            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['master_product_id', 'occurred_at']);

            // İdempotency: aynı (source, source_reference, event_type) tek event
            $table->unique(
                ['source', 'source_reference', 'event_type'],
                'stock_events_source_ref_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_events');
    }
};
