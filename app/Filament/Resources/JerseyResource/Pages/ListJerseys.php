<?php

declare(strict_types=1);

namespace App\Filament\Resources\JerseyResource\Pages;

use App\Filament\Resources\JerseyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListJerseys extends ListRecords
{
    protected static string $resource = JerseyResource::class;

    protected static ?string $title = 'List of products';

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
