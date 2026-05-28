<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();

            $table->foreignId('user_marketplace_credential_id')->nullable()->constrained()->nullOnDelete();
            $table->string('marketplace_code', 32);

            $table->string('event_type', 32); // order_created, order_status_changed, claim_created, etc.
            $table->string('source_reference')->nullable(); // orderNumber, claimId
            $table->json('raw_payload');

            $table->string('status', 16)->default('received'); // received|processing|processed|failed
            $table->timestamp('processed_at')->nullable();
            $table->text('processing_error')->nullable();

            $table->timestamps();

            $table->index(['marketplace_code', 'event_type', 'status']);
            $table->index(['user_marketplace_credential_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_events');
    }
};
