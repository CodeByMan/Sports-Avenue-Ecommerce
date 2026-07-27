<?php

declare(strict_types=1);

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $this->call([
            LeagueSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
            StorefrontSettingSeeder::class,
            JerseySeeder::class,
        ]);
    }
}
