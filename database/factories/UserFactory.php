<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        $firstNames = ['Adeel', 'Ali', 'Ayesha', 'Bilal', 'Fatima', 'Hamza', 'Hira', 'Mahnoor', 'Saad', 'Zainab'];
        $lastNames = ['Ahmed', 'Hussain', 'Iqbal', 'Khan', 'Malik', 'Nawaz', 'Qureshi', 'Raza', 'Sheikh', 'Siddiqui'];
        $areas = ['Gulshan-e-Iqbal, Karachi', 'Johar Town, Lahore', 'F-10, Islamabad', 'Satellite Town, Rawalpindi', 'D Ground, Faisalabad', 'Cantt, Multan'];
        $name = fake()->randomElement($firstNames).' '.fake()->randomElement($lastNames);

        return [
            'city_id' => null,
            'name' => $name,
            'username' => str($name.'-'.Str::lower(Str::random(5)))->slug('')->value(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '03'.fake()->numerify('#########'),
            'role' => fake()->randomElement(User::$roles),
            'address' => fake()->randomElement($areas),
            'status' => User::STATUS_ACTIVE,
            'avatar' => null,
            'password' => Hash::make(
                app()->environment(['local', 'testing'])
                    ? (string) config('demo.password', 'ChangeMe-LocalOnly!')
                    : Str::password(32),
            ),
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
            'created_by' => null,
            'updated_by' => null,
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
