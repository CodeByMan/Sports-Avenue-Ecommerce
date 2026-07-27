<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\StorefrontSetting;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class StorefrontSettingSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $heroPath = 'images/hero_section.png';
        $sourcePath = public_path($heroPath);

        if (File::exists($sourcePath)) {
            Storage::disk('public')->put('storefront/hero-section.png', File::get($sourcePath));
            $heroPath = 'storefront/hero-section.png';
        }

        StorefrontSetting::query()->updateOrCreate(
            ['name' => 'Homepage'],
            [
                'hero_eyebrow' => 'Sports Avenue',
                'hero_title' => 'Wear the Glory',
                'hero_subtitle' => 'Wear your passion on your sleeve.',
                'hero_image' => $heroPath,
                'hero_cta_label' => 'Shop Jerseys',
                'hero_cta_url' => '/jerseys',
                'is_active' => true,
            ],
        );
    }
}
