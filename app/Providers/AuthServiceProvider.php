<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Jersey;
use App\Models\League;
use App\Models\Order;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Policies\BrandPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\JerseyPolicy;
use App\Policies\LeaguePolicy;
use App\Policies\OrderPolicy;
use App\Policies\StorefrontSettingPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Brand::class => BrandPolicy::class,
        Category::class => CategoryPolicy::class,
        Jersey::class => JerseyPolicy::class,
        League::class => LeaguePolicy::class,
        Order::class => OrderPolicy::class,
        StorefrontSetting::class => StorefrontSettingPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void {}
}
