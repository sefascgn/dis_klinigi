<?php

namespace Database\Factories;

use App\Enums\TreatmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\TreatmentType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Treatment>
 */
class TreatmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'treatment_type_id' => TreatmentType::factory(),
            'appointment_id' => null,
            'tooth_number' => fake()->randomElement([11, 16, 21, 26, 36, 46, null]),
            'price' => fake()->randomFloat(2, 500, 15000),
            'status' => TreatmentStatus::Completed,
            'treatment_date' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'notes' => null,
        ];
    }
}
