<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<League>
 */
class LeagueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(5, true);

        return [
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'country' => 'Pakistan',
            'image' => fake()->word(),
            'created_by' => null,
        ];
    }
}
