<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Jersey;
use App\Models\League;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Jersey>
 */
class JerseyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->words(4, true);

        return [
            'league_id' => League::factory(),
            'sku' => 'SA-JER-'.Str::upper(Str::random(12)),
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'type' => fake()->words(3, true),
            'weight' => rand(100, 999),
            'price' => 5000,
            'price_nameset' => 750,
            'image' => fake()->word(),
            'stock' => ['S' => 1, 'M' => 2, 'L' => 3, 'XL' => 4],
            'sold' => rand(0, 20),
            'created_by' => null,
        ];
    }
}
