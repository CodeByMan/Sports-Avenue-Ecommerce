<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Filament\Widgets\CatalogStatsOverview;
use App\Filament\Widgets\RecentOrders;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = -10;

    public function getColumns(): int|string|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 4,
        ];
    }

    public function getWidgets(): array
    {
        return [
            CatalogStatsOverview::class,
            RecentOrders::class,
        ];
    }
}
