<?php

namespace Database\Factories;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(['checking', 'savings', 'credit card', 'cash', 'other']),
            'name' => fake()->words(3, true),
            'currency' => fake()->randomElement(['usd', 'eur', 'gbp', 'jpy']),
            'start_balance' => fake()->numberBetween(0, 1000000),
            'created_by' => User::factory(),
        ];
    }

    public function forTeam(Team $team): static
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $team->id,
        ]);
    }
}
