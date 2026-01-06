<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Currency>
 */
class CurrencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->currencyCode,
            'symbol' => $this->faker->randomElement(['$', '€', '£', '¥', '₡', 'S/', 'R$']),
            'exchange_rate' => $this->faker->randomFloat(4, 0.0001, 1000), 
        ];
    }
}
