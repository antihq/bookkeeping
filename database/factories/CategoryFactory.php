<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
        ];
    }

    public function forTeam(mixed $team): self
    {
        return $this->state(fn (array $attributes) => [
            'team_id' => $team,
        ]);
    }
}
