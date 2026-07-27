<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class BrandSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $brands = [
            ['name' => 'Arsenal', 'country' => 'England', 'image' => 'arsenal 22-23 home.png'],
            ['name' => 'Liverpool', 'country' => 'England', 'image' => 'liv 22-23 home.png'],
            ['name' => 'Manchester City', 'country' => 'England', 'image' => 'man city 22-23 home.png'],
            ['name' => 'Barcelona', 'country' => 'Spain', 'image' => 'fcb 22-23 home.png'],
            ['name' => 'Real Madrid', 'country' => 'Spain', 'image' => 'rma 22-23 home.png'],
            ['name' => 'AC Milan', 'country' => 'Italy', 'image' => 'acm 22-23 home.jpg'],
            ['name' => 'Juventus', 'country' => 'Italy', 'image' => 'juv 22-23 home.jpg'],
            ['name' => 'Bayern Munich', 'country' => 'Germany', 'image' => 'fbm 22-23 home.jpg'],
            ['name' => 'Borussia Dortmund', 'country' => 'Germany', 'image' => 'bvb 22-23 home.jpg'],
            ['name' => 'Paris Saint-Germain', 'country' => 'France', 'image' => 'psg 22-23 home.jpg'],
        ];

        foreach ($brands as $index => $data) {
            $slug = str($data['name'])->slug()->value();
            $storedImage = $this->copyAsset('images/jerseys/'.$data['image'], 'brands/'.$slug.'.'.pathinfo($data['image'], PATHINFO_EXTENSION));

            $brand = Brand::query()->where('slug', $slug)->first() ?? new Brand();
            $brand->fill([
                'name' => $data['name'],
                'slug' => $slug,
                'country' => $data['country'],
                'description' => $data['name'].' football apparel and supporter products.',
                'image' => $storedImage,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
            $brand->save();
        }
    }

    private function copyAsset(string $source, string $destination): string
    {
        $sourcePath = public_path($source);

        if (File::exists($sourcePath)) {
            Storage::disk('public')->put($destination, File::get($sourcePath));

            return $destination;
        }

        return $source;
    }
}
