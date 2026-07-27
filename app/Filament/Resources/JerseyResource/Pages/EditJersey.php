<?php

declare(strict_types=1);

namespace App\Filament\Resources\JerseyResource\Pages;

use App\Filament\Resources\JerseyResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditJersey extends EditRecord
{
    protected static string $resource = JerseyResource::class;

    protected static ?string $title = 'Edit product';

    public function getBreadcrumb(): string
    {
        return trans(self::$title);
    }

    public function getTitle(): string
    {
        return trans(self::$title);
    }

    protected function getFormActions(): array
    {
        return [$this->getSaveFormAction()];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),

            Actions\DeleteAction::make()
                ->label(trans('Delete'))
                ->requiresConfirmation()
                ->modalHeading(
                    trans('Permanently delete this product?')
                )
                ->modalDescription(
                    trans('This action cannot be undone.')
                ),

            Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($data['stock'] as $key => $stock) {
            $data[$key] = $stock;
        }
        unset($data['stock']);

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data = $this->getResource()::mergeSizeFields($data);
        $data['slug'] = str($data['name'])->slug()->value();
        $data['updated_by'] = $this->getResource()::getUser()->id;

        return $data;
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')->label(trans('Update'))->submit('save')->keyBindings(['mod+s']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return trans('Jersey updated successfully');
    }
}
