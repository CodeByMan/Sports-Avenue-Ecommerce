<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeagueResource\Pages;

use App\Filament\Resources\LeagueResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLeagues extends ListRecords
{
    protected static string $resource = LeagueResource::class;

    protected static ?string $title = 'List of leagues';

    public function getBreadcrumb(): ?string
    {
        return trans(self::$title);
    }

    public function getTitle(): string
    {
        return trans(self::$title);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [];
    }
}
