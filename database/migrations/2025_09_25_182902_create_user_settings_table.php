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
        Schema::create('user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('preferred_language_id')->constrained('languages');
            $table->boolean('dark_mode')->default(false);
            $table->json('additional_settings')->nullable(); // Future settings
            $table->timestamps();

            $table->unique('user_id'); // Her kullanıcının bir ayarı olabilir
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_settings');
    }
};
