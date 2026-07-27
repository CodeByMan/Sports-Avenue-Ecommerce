<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Province> */
class ProvinceFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'provider_id' => fake()->unique()->numberBetween(100, 999999),
            'name' => fake()->randomElement([
                'Punjab',
                'Sindh',
                'Khyber Pakhtunkhwa',
                'Balochistan',
                'Gilgit Baltistan',
                'Azad Kashmir',
                'Islamabad Capital Territory',
            ]),
        ];
    }
}
