<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $amount = fake()->numberBetween(-1000000, 1000000);
        $date = fake()->dateTimeBetween('-2 years', 'now');

        return [
            'date' => $date->format('Y-m-d'),
            'payee' => fake()->words(3, true),
            'note' => fake()->boolean(70) ? fake()->sentence() : null,
            'amount' => $amount,
            'created_by' => User::factory(),
        ];
    }
}
