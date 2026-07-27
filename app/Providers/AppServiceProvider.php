<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Category;
use App\Models\Jersey;
use App\Models\League;
use App\Observers\CategoryObserver;
use App\Observers\JerseyObserver;
use App\Observers\LeagueObserver;
use Carbon\Carbon;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Category::observe(CategoryObserver::class);
        Jersey::observe(JerseyObserver::class);
        League::observe(LeagueObserver::class);

        Carbon::setLocale((string) config('app.locale'));
    }
}
