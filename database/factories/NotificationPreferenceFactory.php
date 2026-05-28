<?php

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'notification_type' => fake()->randomElement(['daily_digest', 'critical_stock', 'sync_failure', 'new_question', 'new_claim']),
            'channel' => 'mail',
            'enabled' => true,
            'threshold_value' => null,
            'schedule_time' => '09:00',
        ];
    }
}
