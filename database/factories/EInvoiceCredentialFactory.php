<?php

namespace Database\Factories;

use App\Models\EInvoiceCredential;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EInvoiceCredentialFactory extends Factory
{
    protected $model = EInvoiceCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'provider' => 'parasut',
            'api_key' => $this->faker->sha256(),
            'api_secret' => $this->faker->sha256(),
            'company_tax_number' => $this->faker->numerify('##########'),
            'is_active' => false,
            'additional_config' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
        ]);
    }

    public function forProvider(string $provider): static
    {
        return $this->state(fn () => [
            'provider' => $provider,
        ]);
    }
}
