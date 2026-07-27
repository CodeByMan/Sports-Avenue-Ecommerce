<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Jersey;
use App\Models\League;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class JerseySeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        // Keep this seeder independently runnable because the existing test suite and
        // developer workflows invoke JerseySeeder directly. Each dependency seeder is
        // idempotent, so this is safe when CatalogSeeder has already run them.
        $this->call([
            LeagueSeeder::class,
            BrandSeeder::class,
            CategorySeeder::class,
        ]);

        $products = [
            ['league' => 'Premier League', 'brand' => 'Arsenal', 'home' => 'arsenal 22-23 home.png', 'away' => 'arsenal 22-23 away.jpg', 'legacy' => 'northbridge-falcons', 'price' => 4800],
            ['league' => 'Premier League', 'brand' => 'Liverpool', 'home' => 'liv 22-23 home.png', 'away' => 'liv 22-23 away.png', 'legacy' => 'harbor-city', 'price' => 4800],
            ['league' => 'Premier League', 'brand' => 'Manchester City', 'home' => 'man city 22-23 home.png', 'away' => 'man city 22-23 away.png', 'legacy' => 'azure-olympique', 'price' => 5000],
            ['league' => 'La Liga', 'brand' => 'Barcelona', 'home' => 'fcb 22-23 home.png', 'away' => 'fcb 22-23 away.jpg', 'legacy' => 'solara-athletic', 'price' => 5000],
            ['league' => 'La Liga', 'brand' => 'Real Madrid', 'home' => 'rma 22-23 home.png', 'away' => 'rma 22-23 away.jpg', 'legacy' => 'costa-verde', 'price' => 5200],
            ['league' => 'Serie A', 'brand' => 'AC Milan', 'home' => 'acm 22-23 home.jpg', 'away' => 'acm 22-23 away.jpg', 'legacy' => 'aurora-calcio', 'price' => 4600],
            ['league' => 'Serie A', 'brand' => 'Juventus', 'home' => 'juv 22-23 home.jpg', 'away' => 'juv 22-23 away.jpg', 'legacy' => 'vesuvio-united', 'price' => 4600],
            ['league' => 'Bundes League', 'brand' => 'Bayern Munich', 'home' => 'fbm 22-23 home.jpg', 'away' => 'fbm 22-23 away.jpg', 'legacy' => 'rhine-wolves', 'price' => 4800],
            ['league' => 'Bundes League', 'brand' => 'Borussia Dortmund', 'home' => 'bvb 22-23 home.jpg', 'away' => 'bvb 22-23 away.jpg', 'legacy' => 'alpine-04', 'price' => 4600],
            ['league' => 'Ligue 1', 'brand' => 'Paris Saint-Germain', 'home' => 'psg 22-23 home.jpg', 'away' => 'psg 22-23 away.jpg', 'legacy' => 'capital-comets', 'price' => 5000],
        ];

        foreach ($products as $productIndex => $product) {
            $league = League::query()->where('name', $product['league'])->first();
            $brand = Brand::query()->where('name', $product['brand'])->first();

            if ($league === null || $brand === null) {
                throw new RuntimeException('Catalog relationships must be seeded before products.');
            }

            foreach (['Home' => $product['home'], 'Away' => $product['away']] as $variant => $sourceFile) {
                $category = Category::query()->where('name', $variant.' Kits')->firstOrFail();
                $slug = str($product['brand'].' '.$variant.' Jersey')->slug()->value();
                $sku = 'SA-'.str($product['brand'])->upper()->replaceMatches('/[^A-Z0-9]/', '')->substr(0, 5)->value().'-'.strtoupper(substr($variant, 0, 1));
                $imagePath = $this->copyAsset($sourceFile);
                $alternatePath = $this->copyAsset($variant === 'Home' ? $product['away'] : $product['home']);
                $legacyImage = $product['legacy'].'-'.strtolower($variant).'.png';

                $jersey = Jersey::query()
                    ->where('sku', $sku)
                    ->orWhere('slug', $slug)
                    ->orWhere('image', $legacyImage)
                    ->first();
                $jersey ??= new Jersey();
                $isNewProduct = ! $jersey->exists;
                $attributes = [
                    'league_id' => $league->id,
                    'brand_id' => $brand->id,
                    'category_id' => $category->id,
                    'sku' => $sku,
                    'name' => $product['brand'].' '.$variant.' Jersey',
                    'slug' => $slug,
                    'type' => $variant.' Kit',
                    'description' => 'Premium '.$product['brand'].' '.$variant.' supporter jersey for match day and everyday wear.',
                    'weight' => 500,
                    'price' => $product['price'],
                    'price_nameset' => 750,
                    'image' => $imagePath,
                    'gallery' => [$imagePath, $alternatePath, $imagePath],
                    'is_active' => true,
                    'is_featured' => $variant === 'Home' && $productIndex < 4,
                ];

                if ($isNewProduct) {
                    $attributes['stock'] = ['S' => 8, 'M' => 12, 'L' => 10, 'XL' => 6];
                    $attributes['sold'] = max(4, 22 - ($productIndex * 2) - ($variant === 'Away' ? 1 : 0));
                }

                $jersey->fill($attributes);
                $jersey->save();
            }
        }
    }

    private function copyAsset(string $sourceFile): string
    {
        $sourcePath = public_path('images/jerseys/'.$sourceFile);
        $destination = 'jerseys/'.str($sourceFile)->lower()->replace(' ', '-')->value();

        if (File::exists($sourcePath)) {
            Storage::disk('public')->put($destination, File::get($sourcePath));

            return $destination;
        }

        return 'images/jerseys/'.$sourceFile;
    }
}
