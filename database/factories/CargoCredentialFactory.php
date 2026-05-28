<?php

namespace Database\Factories;

use App\Models\CargoCredential;
use App\Models\CargoProvider;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoCredentialFactory extends Factory
{
    protected $model = CargoCredential::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'cargo_provider_id' => CargoProvider::factory(),
            'username' => $this->faker->userName(),
            'password' => $this->faker->password(),
            'customer_code' => strtoupper($this->faker->bothify('???######')),
            'is_active' => false,
            'ip_whitelisted_at' => null,
            'additional_config' => [],
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'is_active' => true,
            'ip_whitelisted_at' => now(),
        ]);
    }

    public function forProvider(CargoProvider $provider): static
    {
        return $this->state(fn () => [
            'cargo_provider_id' => $provider->id,
        ]);
    }
}
