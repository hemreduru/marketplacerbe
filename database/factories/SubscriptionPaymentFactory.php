<?php

namespace Database\Factories;

use App\Models\Plan;
use App\Models\SubscriptionPayment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<SubscriptionPayment>
 */
class SubscriptionPaymentFactory extends Factory
{
    protected $model = SubscriptionPayment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => fn () => Plan::query()->value('id'),
            'billing_period' => 'monthly',
            'amount' => '499.0000',
            'currency' => 'TRY',
            'status' => 'pending',
            'conversation_id' => (string) Str::uuid(),
            'payment_id' => null,
            'error_message' => null,
            'paid_at' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => 'success',
            'payment_id' => 'pay_'.fake()->numerify('##########'),
            'paid_at' => now(),
        ]);
    }
}
