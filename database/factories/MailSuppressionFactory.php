<?php

namespace Database\Factories;

use App\Models\MailSuppression;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailSuppression>
 */
class MailSuppressionFactory extends Factory
{
    protected $model = MailSuppression::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'reason' => fake()->randomElement(['bounce', 'complaint']),
            'raw' => [
                'notificationType' => fake()->randomElement(['Bounce', 'Complaint']),
                'mail' => [
                    'timestamp' => now()->toIso8601String(),
                    'messageId' => fake()->uuid(),
                ],
            ],
        ];
    }
}
