<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('notification_type');
            $table->string('channel')->default('mail');
            $table->boolean('enabled')->default(true);
            $table->json('threshold_value')->nullable();
            $table->time('schedule_time')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'notification_type', 'channel'], 'np_user_type_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_preferences');
    }
};
