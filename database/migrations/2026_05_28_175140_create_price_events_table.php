<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_listing_id')->nullable()->constrained()->nullOnDelete();

            $table->string('event_type', 32);
            $table->string('source', 32);
            $table->string('source_reference')->nullable();

            $table->decimal('new_price', 15, 4);
            $table->decimal('previous_price', 15, 4)->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['master_product_id', 'occurred_at']);

            $table->unique(
                ['source', 'source_reference', 'event_type'],
                'price_events_source_ref_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_events');
    }
};
