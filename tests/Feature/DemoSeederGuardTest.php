<?php

declare(strict_types=1);

namespace Tests\Feature;

use Database\Seeders\JerseySeeder;
use Database\Seeders\LeagueSeeder;
use Database\Seeders\RegionSeeder;
use Database\Seeders\SampleOrderSeeder;
use LogicException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoSeederGuardTest extends TestCase
{
    #[DataProvider('guardedSeederProvider')]
    public function test_direct_demo_seeders_are_blocked_in_production(string $seederClass): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config()->set('demo.allow_seeding', false);

        $this->expectException(LogicException::class);
        $this->app->make($seederClass)->run();
    }

    /** @return array<string, array{0:class-string}> */
    public static function guardedSeederProvider(): array
    {
        return [
            'regions' => [RegionSeeder::class],
            'leagues' => [LeagueSeeder::class],
            'jerseys' => [JerseySeeder::class],
            'sample orders' => [SampleOrderSeeder::class],
        ];
    }
}
