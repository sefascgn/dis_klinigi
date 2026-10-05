<?php

namespace Database\Factories;

use App\Enums\Gender;
use App\Enums\MedicalCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'national_id' => fake()->unique()->numerify('1##########'),
            'birth_date' => fake()->dateTimeBetween('-80 years', '-5 years')->format('Y-m-d'),
            'gender' => fake()->randomElement([Gender::Female, Gender::Male]),
            'phone' => '05'.fake()->numerify('#########'),
            'email' => fake()->optional()->safeEmail(),
            'address' => fake()->optional()->address(),
            'allergies' => fake()->optional(0.2)->randomElement(['Penisilin', 'Lateks', 'Lokal anestezik']),
            'medical_conditions' => fake()->boolean(25) ? fake()->randomElements(MedicalCondition::cases(), fake()->numberBetween(1, 2)) : [],
            'medical_notes' => null,
        ];
    }
}
