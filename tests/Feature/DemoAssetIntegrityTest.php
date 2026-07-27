<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Jersey;
use App\Models\League;
use App\Models\OrderItem;
use Database\Seeders\JerseySeeder;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoAssetIntegrityTest extends TestCase
{
    public function test_seeded_demo_catalog_uses_packaged_project_assets(): void
    {
        $this->seed(JerseySeeder::class);

        $leagues = League::query()->orderBy('id')->get();
        $jerseys = Jersey::query()->orderBy('id')->get();

        $this->assertCount(5, $leagues);
        $this->assertCount(20, $jerseys);

        foreach ($leagues as $league) {
            $this->assertCatalogAssetExists($league->image);
            $this->assertNotSame(asset('assets/images/no-image.png'), $league->getImage());
        }

        foreach ($jerseys as $jersey) {
            $this->assertCatalogAssetExists($jersey->image);
            $this->assertNotSame(asset('assets/images/no-image.png'), $jersey->getImage());
        }

        $catalogText = $leagues->pluck('name')
            ->merge($jerseys->pluck('name'))
            ->implode(' ');

        foreach ([
            'Premier League',
            'La Liga',
            'Serie A',
            'Bundes League',
            'Ligue 1',
            'Arsenal',
            'Liverpool',
            'Manchester City',
            'Barcelona',
            'Real Madrid',
            'AC Milan',
            'Juventus',
            'Bayern Munich',
            'Borussia Dortmund',
            'Paris Saint-Germain',
        ] as $expectedName) {
            $this->assertStringContainsString($expectedName, $catalogText);
        }
    }

    public function test_missing_catalog_images_use_the_project_placeholder(): void
    {
        Storage::fake('public');

        $league = League::factory()->make(['image' => 'missing-league.png']);
        $jersey = Jersey::factory()->make(['image' => 'missing-jersey.png']);
        $orderItem = new OrderItem(['image_path' => 'missing-order-image.png']);
        $placeholder = asset('assets/images/no-image.png');

        $this->assertSame($placeholder, $league->getImage());
        $this->assertSame($placeholder, $jersey->getImage());
        $this->assertSame($placeholder, $orderItem->image_url);
    }

    public function test_uploaded_catalog_images_still_use_public_storage(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('leagues/custom-league.png', 'generated-test-file');
        Storage::disk('public')->put('jerseys/custom-jersey.png', 'generated-test-file');

        $league = League::factory()->make(['image' => 'custom-league.png']);
        $jersey = Jersey::factory()->make(['image' => 'custom-jersey.png']);
        $orderItem = new OrderItem(['image_path' => 'custom-jersey.png']);

        $this->assertSame(asset('storage/leagues/custom-league.png'), $league->getImage());
        $this->assertSame(asset('storage/jerseys/custom-jersey.png'), $jersey->getImage());
        $this->assertSame(asset('storage/jerseys/custom-jersey.png'), $orderItem->image_url);
    }

    private function assertCatalogAssetExists(?string $path): void
    {
        $normalizedPath = ltrim(str_replace('\\', '/', (string) $path), '/');

        $this->assertTrue(
            file_exists(public_path($normalizedPath)) || Storage::disk('public')->exists($normalizedPath),
            "Catalog asset [{$normalizedPath}] does not exist in public/ or the public storage disk.",
        );
    }
}
