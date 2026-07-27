<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Category;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CategorySeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $categories = [
            ['name' => 'Home Kits', 'description' => 'Official-style home colourways.', 'image' => 'arsenal 22-23 home.png'],
            ['name' => 'Away Kits', 'description' => 'Alternative match-day colourways.', 'image' => 'arsenal 22-23 away.jpg'],
            ['name' => 'Supporter Jerseys', 'description' => 'Everyday supporter apparel.', 'image' => 'fcb 22-23 home.png'],
        ];

        foreach ($categories as $index => $data) {
            $slug = str($data['name'])->slug()->value();
            $extension = pathinfo($data['image'], PATHINFO_EXTENSION);
            $storedImage = $this->copyAsset('images/jerseys/'.$data['image'], 'categories/'.$slug.'.'.$extension);

            $category = Category::query()->where('slug', $slug)->first() ?? new Category();
            $category->fill([
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'],
                'image' => $storedImage,
                'is_active' => true,
                'sort_order' => $index + 1,
            ]);
            $category->save();
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
