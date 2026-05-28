<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_dispatch_queue', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_listing_id')->constrained()->cascadeOnDelete();

            $table->string('mutation_type', 32); // stock | price | stock_and_price | listing_update
            $table->json('payload_json');

            $table->string('status', 16)->default('pending'); // pending|sent|failed|skipped
            $table->unsignedTinyInteger('attempt_count')->default(0);
            $table->timestamp('last_attempt_at')->nullable();
            $table->text('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'next_attempt_at']);
            $table->index(['marketplace_listing_id', 'mutation_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_dispatch_queue');
    }
};
