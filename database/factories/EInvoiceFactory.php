<?php

namespace Database\Factories;

use App\Models\EInvoice;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EInvoiceFactory extends Factory
{
    protected $model = EInvoice::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => null,
            'provider' => 'parasut',
            'invoice_uuid' => $this->faker->uuid(),
            'e_invoice_number' => null,
            'e_archive_number' => null,
            'status' => 'draft',
            'subtotal' => $this->faker->randomFloat(4, 100, 10000),
            'total_vat' => $this->faker->randomFloat(4, 20, 2000),
            'total_amount' => $this->faker->randomFloat(4, 120, 12000),
            'raw_response' => [],
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => 'approved',
            'issued_at' => now(),
        ]);
    }

    public function forOrder(Order $order): static
    {
        return $this->state(fn () => [
            'order_id' => $order->id,
            'user_id' => $order->user_id,
        ]);
    }
}
