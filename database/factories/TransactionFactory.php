<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    protected $model = \App\Models\Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => \App\Models\Order::factory(),
            'ext_id' => 'pi_' . fake()->unique()->regexify('[a-zA-Z0-9]{24}'),
            'type' => 'stripe',
            'status' => 'succeeded',
            'total' => fake()->numberBetween(500, 50000),
        ];
    }

    /**
     * Indicate that the transaction failed.
     */
    public function failed(): static
    {
        return $this->state(fn () => ['status' => 'failed']);
    }

    /**
     * Indicate that the transaction is a wallet payment.
     */
    public function wallet(): static
    {
        return $this->state(fn () => [
            'type' => 'wallet',
            'ext_id' => null,
        ]);
    }
}
