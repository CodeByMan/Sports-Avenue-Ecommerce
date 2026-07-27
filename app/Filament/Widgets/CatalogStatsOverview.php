<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\JerseyResource;
use App\Filament\Resources\OrderResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Jersey;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CatalogStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Active products', Jersey::query()->active()->count())
                ->description('Managed catalogue products')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->icon('heroicon-o-shopping-bag')
                ->color('primary')
                ->url(JerseyResource::getUrl()),
            Stat::make('Brands', Brand::query()->active()->count())
                ->description('Active storefront brands')
                ->icon('heroicon-o-building-storefront')
                ->color('primary')
                ->url(BrandResource::getUrl()),
            Stat::make('Categories', Category::query()->active()->count())
                ->description('Dynamic product categories')
                ->icon('heroicon-o-squares-2x2')
                ->color('primary')
                ->url(CategoryResource::getUrl()),
            Stat::make('Orders', Order::query()->where('status', '!=', Order::STATUS_IN_CART)->count())
                ->description('Submitted customer orders')
                ->icon('heroicon-o-shopping-cart')
                ->color('success')
                ->url(OrderResource::getUrl()),
        ];
    }
}
