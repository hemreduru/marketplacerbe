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
        Schema::create('repricer_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_product_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('strategy', 32)->default('fixed'); // target_margin | undercut | fixed
            $table->decimal('min_price', 15, 4)->nullable();
            $table->decimal('max_price', 15, 4)->nullable();
            $table->decimal('target_margin', 6, 2)->nullable();
            $table->decimal('undercut_amount', 15, 4)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repricer_rules');
    }
};
