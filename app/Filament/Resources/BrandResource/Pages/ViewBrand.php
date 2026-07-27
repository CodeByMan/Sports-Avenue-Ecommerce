<?php

declare(strict_types=1);

namespace App\Filament\Resources\BrandResource\Pages;

use App\Filament\Resources\BrandResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewBrand extends ViewRecord
{
    protected static string $resource = BrandResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make()
                ->label(trans('Delete'))
                ->requiresConfirmation()
                ->modalHeading(trans('Permanently delete this brand?'))
                ->modalDescription(trans('Products assigned to this brand will be kept and changed to unbranded.'))
                ->successNotificationTitle(trans('Brand deleted permanently')),
        ];
    }
}
