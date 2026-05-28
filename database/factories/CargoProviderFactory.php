<?php

namespace Database\Factories;

use App\Models\CargoProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class CargoProviderFactory extends Factory
{
    protected $model = CargoProvider::class;

    public function definition(): array
    {
        return [
            'code' => $this->faker->unique()->slug(1),
            'name' => $this->faker->company(),
            'protocol' => 'soap',
            'has_webhook' => false,
            'label_formats' => ['a4_pdf', 'zpl'],
            'is_active' => true,
            'config' => [],
        ];
    }

    public function yurtici(): static
    {
        return $this->state(fn () => [
            'code' => 'yurtici',
            'name' => 'Yurtiçi Kargo',
            'protocol' => 'soap',
            'has_webhook' => false,
        ]);
    }

    public function aras(): static
    {
        return $this->state(fn () => [
            'code' => 'aras',
            'name' => 'Aras Kargo',
            'protocol' => 'soap',
            'has_webhook' => true,
        ]);
    }

    public function mng(): static
    {
        return $this->state(fn () => [
            'code' => 'mng',
            'name' => 'MNG Kargo',
            'protocol' => 'soap',
            'has_webhook' => false,
        ]);
    }
}
