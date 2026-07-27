<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\League;
use Database\Seeders\Concerns\GuardsDemoSeeding;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class LeagueSeeder extends Seeder
{
    use GuardsDemoSeeding;

    public function run(): void
    {
        $this->ensureDemoSeedingIsAllowed();

        $leagues = [
            ['name' => 'Premier League', 'country' => 'England', 'source' => 'Premier League.png'],
            ['name' => 'La Liga', 'country' => 'Spain', 'source' => 'La Liga.png'],
            ['name' => 'Serie A', 'country' => 'Italy', 'source' => 'Serie A.png'],
            ['name' => 'Bundes League', 'country' => 'Germany', 'source' => 'Bundes League.png'],
            ['name' => 'Ligue 1', 'country' => 'France', 'source' => 'Ligue 1.png'],
        ];

        foreach ($leagues as $data) {
            $slug = str($data['name'])->slug()->value();
            $destination = 'leagues/'.$slug.'.png';
            $sourcePath = public_path('images/leagues/'.$data['source']);

            if (File::exists($sourcePath)) {
                Storage::disk('public')->put($destination, File::get($sourcePath));
            } else {
                $destination = 'images/leagues/'.$data['source'];
            }

            $league = League::query()->where('country', $data['country'])->first();
            $league ??= new League();
            $league->fill([
                'name' => $data['name'],
                'slug' => $slug,
                'country' => $data['country'],
                'image' => $destination,
            ]);
            $league->save();
        }
    }
}
