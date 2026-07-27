<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\City;
use App\Models\Province;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegionSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $regions = [
            ['provider_id' => 101, 'name' => 'Sindh', 'cities' => [
                ['provider_id' => 1001, 'name' => 'Karachi', 'type' => 'City', 'postal_code' => '74000'],
                ['provider_id' => 1002, 'name' => 'Hyderabad', 'type' => 'City', 'postal_code' => '71000'],
                ['provider_id' => 1003, 'name' => 'Sukkur', 'type' => 'City', 'postal_code' => '65200'],
            ]],
            ['provider_id' => 102, 'name' => 'Punjab', 'cities' => [
                ['provider_id' => 1101, 'name' => 'Lahore', 'type' => 'City', 'postal_code' => '54000'],
                ['provider_id' => 1102, 'name' => 'Rawalpindi', 'type' => 'City', 'postal_code' => '46000'],
                ['provider_id' => 1103, 'name' => 'Faisalabad', 'type' => 'City', 'postal_code' => '38000'],
                ['provider_id' => 1104, 'name' => 'Multan', 'type' => 'City', 'postal_code' => '60000'],
                ['provider_id' => 1105, 'name' => 'Gujranwala', 'type' => 'City', 'postal_code' => '52250'],
                ['provider_id' => 1106, 'name' => 'Sialkot', 'type' => 'City', 'postal_code' => '51310'],
            ]],
            ['provider_id' => 103, 'name' => 'Islamabad Capital Territory', 'cities' => [
                ['provider_id' => 1201, 'name' => 'Islamabad', 'type' => 'City', 'postal_code' => '44000'],
            ]],
            ['provider_id' => 104, 'name' => 'Khyber Pakhtunkhwa', 'cities' => [
                ['provider_id' => 1301, 'name' => 'Peshawar', 'type' => 'City', 'postal_code' => '25000'],
                ['provider_id' => 1302, 'name' => 'Abbottabad', 'type' => 'City', 'postal_code' => '22010'],
                ['provider_id' => 1303, 'name' => 'Mardan', 'type' => 'City', 'postal_code' => '23200'],
            ]],
            ['provider_id' => 105, 'name' => 'Balochistan', 'cities' => [
                ['provider_id' => 1401, 'name' => 'Quetta', 'type' => 'City', 'postal_code' => '87300'],
                ['provider_id' => 1402, 'name' => 'Gwadar', 'type' => 'City', 'postal_code' => '91200'],
            ]],
            ['provider_id' => 106, 'name' => 'Gilgit Baltistan', 'cities' => [
                ['provider_id' => 1501, 'name' => 'Gilgit', 'type' => 'City', 'postal_code' => '15100'],
                ['provider_id' => 1502, 'name' => 'Skardu', 'type' => 'City', 'postal_code' => '16100'],
            ]],
            ['provider_id' => 107, 'name' => 'Azad Kashmir', 'cities' => [
                ['provider_id' => 1601, 'name' => 'Muzaffarabad', 'type' => 'City', 'postal_code' => '13100'],
                ['provider_id' => 1602, 'name' => 'Mirpur', 'type' => 'City', 'postal_code' => '10250'],
            ]],
        ];

        DB::transaction(function () use ($regions): void {
            foreach ($regions as $region) {
                $province = Province::query()->updateOrCreate(
                    ['provider_id' => $region['provider_id']],
                    ['name' => $region['name']],
                );

                foreach ($region['cities'] as $city) {
                    City::query()->updateOrCreate(
                        ['provider_id' => $city['provider_id']],
                        array_merge($city, ['province_id' => $province->id]),
                    );
                }
            }

            // Remove only the superseded Indonesian demo rows. Do not erase
            // unrelated regions that a developer may have created locally.
            City::query()
                ->whereIn('provider_id', range(1, 8))
                ->orWhereIn('name', [
                    'Central Jakarta',
                    'South Jakarta',
                    'Bandung',
                    'Bogor',
                    'Semarang',
                    'Surakarta',
                    'Surabaya',
                    'Malang',
                ])
                ->delete();

            Province::query()
                ->whereIn('provider_id', range(1, 4))
                ->orWhereIn('name', ['DKI Jakarta', 'West Java', 'Central Java', 'East Java'])
                ->delete();
        });
    }
}
