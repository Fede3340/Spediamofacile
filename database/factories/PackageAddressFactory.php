<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackageAddress>
 */
class PackageAddressFactory extends Factory
{
    protected $model = \App\Models\PackageAddress::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['Partenza', 'Destinazione']),
            'name' => fake()->name(),
            'address' => fake()->streetAddress(),
            'address_number' => fake()->buildingNumber(),
            'postal_code' => fake()->numerify('#####'),
            'city' => fake()->city(),
            'province' => fake()->randomElement(['MI', 'RM', 'NA', 'TO', 'FI', 'BO', 'PA', 'GE']),
            'country' => 'Italia',
            'number_type' => 'Numero Civico',
            'telephone_number' => fake()->phoneNumber(),
            'email' => fake()->safeEmail(),
        ];
    }
}
