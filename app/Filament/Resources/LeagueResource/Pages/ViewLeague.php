<?php

declare(strict_types=1);

namespace App\Filament\Resources\LeagueResource\Pages;

use App\Filament\Resources\LeagueResource;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewLeague extends ViewRecord
{
    protected static string $resource = LeagueResource::class;

    protected static ?string $title = 'Detail of league';

    public function getBreadcrumb(): string
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
            Actions\EditAction::make(),

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

            Actions\Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label(trans('Name')),
                TextInput::make('country')
                    ->label(trans('Country')),
                FileUpload::make('image')
                    ->label(trans('Image'))
                    ->disk('public')
                    ->disabled(),
                TextInput::make('created_by')
                    ->label(trans('Created by'))
                    ->formatStateUsing(fn (?int $state = null): string => $state === null ? '' : (User::find($state)?->name ?? '')),
            ]);
    }
}
