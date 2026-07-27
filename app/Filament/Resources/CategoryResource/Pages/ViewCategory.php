<?php

declare(strict_types=1);

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->label(trans('Delete'))
                ->requiresConfirmation()
                ->modalHeading(
                    trans('Permanently delete this category?')
                )
                ->modalDescription(
                    trans('This action cannot be undone.')
                ),
        ];
    }
}
