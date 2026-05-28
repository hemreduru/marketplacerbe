<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->string('return_reason', 64)->nullable()->after('status');
            $table->string('return_tracking_number', 100)->nullable()->after('return_reason');
            $table->string('return_carrier', 64)->nullable()->after('return_tracking_number');
            $table->decimal('refund_amount', 15, 4)->nullable()->after('return_carrier');
            $table->timestamp('approved_at')->nullable()->after('refund_amount');
            $table->boolean('restock')->default(false)->after('approved_at');
            $table->timestamp('restocked_at')->nullable()->after('restock');
            $table->text('resolution_notes')->nullable()->after('restocked_at');
        });
    }

    public function down(): void
    {
        Schema::table('claims', function (Blueprint $table) {
            $table->dropColumn([
                'return_reason',
                'return_tracking_number',
                'return_carrier',
                'refund_amount',
                'approved_at',
                'restock',
                'restocked_at',
                'resolution_notes',
            ]);
        });
    }
};
