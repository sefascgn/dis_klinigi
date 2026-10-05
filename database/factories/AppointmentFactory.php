<?php

namespace Database\Factories;

use App\Enums\AppointmentStatus;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Appointment>
 */
class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        $start = Carbon::instance(fake()->dateTimeBetween('+1 day', '+30 days'))
            ->setTime(fake()->numberBetween(9, 17), fake()->randomElement([0, 30]));

        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => Doctor::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes(30),
            'status' => AppointmentStatus::Pending,
            'notes' => null,
        ];
    }

    public function at(Carbon $start, int $minutes = 30): static
    {
        return $this->state(fn () => [
            'starts_at' => $start,
            'ends_at' => $start->copy()->addMinutes($minutes),
        ]);
    }

    public function status(AppointmentStatus $status): static
    {
        return $this->state(fn () => ['status' => $status]);
    }
}
