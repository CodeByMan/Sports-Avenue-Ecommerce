<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeagueResource\Pages;

use App\Filament\Resources\LeagueResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;

class EditLeague extends EditRecord
{
    protected static string $resource = LeagueResource::class;

    protected static ?string $title = 'Edit league';

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
                ->modalHeading(trans('Permanently delete this league?'))
                ->modalDescription(
                    fn (): string => LeagueResource::deleteModalDescription($this->record),
                )
                ->form(
                    fn (): array => LeagueResource::deleteActionForm($this->record),
                )
                ->action(
                    fn (array $data): bool => LeagueResource::deleteWithReplacement($this->record, $data),
                )
                ->successRedirectUrl($this->getResource()::getUrl())
                ->successNotificationTitle(trans('League deleted permanently')),

            Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
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
        return trans('League updated successfully');
    }
}
