<?php

namespace Database\Factories;

use App\Enums\PaymentMethod;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'amount' => fake()->randomFloat(2, 200, 5000),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'paid_at' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'notes' => null,
            'received_by' => null,
        ];
    }
}
