<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use App\Models\User;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $this->call(RegionSeeder::class);

        $password = app()->environment(['local', 'testing'])
            ? (string) config('demo.password')
            : Str::password(32);

        $users = [
            [
                'role' => User::ROLE_ADMIN,
                'name' => 'Muhammad Ali Nawaz',
                'username' => 'admindemo',
                'email' => 'admin@sports-avenue.test',
                'phone' => '03001234567',
                'address' => 'Shahrah-e-Faisal, Karachi',
                'city_provider_id' => 1001,
            ],
            [
                'role' => User::ROLE_STAFF,
                'name' => 'Ayesha Khan',
                'username' => 'staffdemo',
                'email' => 'staff@sports-avenue.test',
                'phone' => '03111234567',
                'address' => 'Gulberg III, Lahore',
                'city_provider_id' => 1101,
            ],
            [
                'role' => User::ROLE_CUSTOMER,
                'name' => 'Hamza Ahmed',
                'username' => 'customerdemo',
                'email' => 'customer@sports-avenue.test',
                'phone' => '03221234567',
                'address' => 'F-10 Markaz, Islamabad',
                'city_provider_id' => 1201,
            ],
        ];

        foreach ($users as $data) {
            $user = User::query()->where('email', $data['email'])->first() ?? new User;
            $user->forceFill([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'address' => $data['address'],
                'city_id' => City::query()->where('provider_id', $data['city_provider_id'])->value('id'),
                'role' => $data['role'],
                'status' => User::STATUS_ACTIVE,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
            ])->save();
        }

        $this->call([
            CatalogSeeder::class,
            SampleOrderSeeder::class,
        ]);
    }
}
