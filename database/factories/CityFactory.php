<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\City;
use App\Models\Province;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<City> */
class CityFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $city = fake()->randomElement([
            ['name' => 'Karachi', 'postal_code' => '74000'],
            ['name' => 'Lahore', 'postal_code' => '54000'],
            ['name' => 'Islamabad', 'postal_code' => '44000'],
            ['name' => 'Rawalpindi', 'postal_code' => '46000'],
            ['name' => 'Faisalabad', 'postal_code' => '38000'],
            ['name' => 'Multan', 'postal_code' => '60000'],
            ['name' => 'Peshawar', 'postal_code' => '25000'],
            ['name' => 'Quetta', 'postal_code' => '87300'],
        ]);

        return [
            'province_id' => Province::factory(),
            'provider_id' => fake()->unique()->numberBetween(1000, 999999),
            'name' => $city['name'],
            'type' => 'City',
            'postal_code' => $city['postal_code'],
        ];
    }
}
