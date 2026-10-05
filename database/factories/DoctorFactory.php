<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Doctor>
 */
class DoctorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Dt.', 'Uzm. Dt.', 'Dr. Dt.']),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'specialty' => fake()->randomElement([
                'Genel Diş Hekimliği', 'Ortodonti', 'Endodonti', 'Periodontoloji',
                'Ağız, Diş ve Çene Cerrahisi', 'Pedodonti', 'Protetik Diş Tedavisi',
            ]),
            'phone' => '05'.fake()->numerify('#########'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }
}
