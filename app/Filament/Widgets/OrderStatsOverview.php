<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\JerseyResource;
use App\Filament\Resources\OrderResource;
use App\Models\Jersey;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OrderStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make(trans('Jerseys count'), Jersey::count())
                ->color('primary')
                ->url(JerseyResource::getUrl()),
            Stat::make(trans('Orders count'), Order::count())
                ->color('success')
                ->url(OrderResource::getUrl()),
        ];
    }
}
