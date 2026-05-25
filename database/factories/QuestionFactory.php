<?php

namespace Database\Factories;

use App\Models\Question;
use App\Models\UserMarketplaceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'user_marketplace_credential_id' => UserMarketplaceCredential::factory(),
            'remote_id' => (string) fake()->unique()->numberBetween(1000000, 9999999),
            'question_text' => fake()->sentence(),
            'answer_text' => null,
            'status' => 'WAITING_FOR_ANSWER',
            'product_name' => fake()->words(3, true),
            'question_date' => fake()->dateTimeBetween('-15 days', 'now'),
            'answered_date' => null,
            'raw_data' => [],
        ];
    }

    public function answered(): static
    {
        return $this->state(fn () => [
            'status' => 'ANSWERED',
            'answer_text' => fake()->sentence(),
            'answered_date' => fake()->dateTimeBetween('-10 days', 'now'),
        ]);
    }
}
