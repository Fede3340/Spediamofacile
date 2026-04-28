<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = \App\Models\Order::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'status' => 'pending',
            'subtotal' => fake()->numberBetween(500, 50000),
            'payment_method' => 'stripe',
            'is_cod' => false,
            'cod_amount' => null,
        ];
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn () => ['status' => 'completed']);
    }

    /**
     * Indicate that the order is processing.
     */
    public function processing(): static
    {
        return $this->state(fn () => ['status' => 'processing']);
    }

    /**
     * Indicate that the order payment failed.
     */
    public function paymentFailed(): static
    {
        return $this->state(fn () => ['status' => 'payment_failed']);
    }

    /**
     * Indicate that the order has BRT shipping data.
     */
    public function withBrt(): static
    {
        return $this->state(fn () => [
            'brt_tracking_number' => fake()->unique()->numerify('BRT##########'),
            'brt_label_base64' => base64_encode(fake()->text(200)),
        ]);
    }
}
