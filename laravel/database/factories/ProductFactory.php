<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph,
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'currency_id' => \App\Models\Currency::factory(),
            'tax_cost' => $this->faker->randomFloat(2, 0, 100),
            'manufacturing_cost' => $this->faker->randomFloat(2, 0, 500),
        ];
    }
}
