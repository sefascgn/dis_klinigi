<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TreatmentType>
 */
class TreatmentTypeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'default_price' => fake()->randomFloat(2, 500, 20000),
            'description' => null,
            'is_active' => true,
        ];
    }
}
