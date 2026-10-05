<?php

namespace Database\Factories;

use App\Enums\ExpenseCategory;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(ExpenseCategory::cases()),
            'description' => fake()->sentence(3),
            'amount' => fake()->randomFloat(2, 500, 20000),
            'method' => fake()->randomElement(PaymentMethod::cases()),
            'spent_at' => fake()->dateTimeBetween('-6 months')->format('Y-m-d'),
            'notes' => null,
            'created_by' => null,
        ];
    }
}
