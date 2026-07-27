<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'Edit user';

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
        return [
            $this->getSaveFormAction(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->label(trans('Delete'))
                ->requiresConfirmation()
                ->modalHeading(
                    trans('Permanently delete this user?')
                )
                ->modalDescription(
                    trans(
                        'Users with order history and the last administrator cannot be deleted.'
                    )
                )
                ->visible(
                    fn (): bool => UserResource::canDelete($this->record)
                )
                ->disabled(function (): bool {
                    if ($this->record->orders()->exists()) {
                        return true;
                    }

                    return $this->record->role === User::ROLE_ADMIN
                        && ! User::query()
                            ->where('role', User::ROLE_ADMIN)
                            ->whereKeyNot($this->record->getKey())
                            ->exists();
                }),

            Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['updated_by'] = $this->getResource()::getUser()->id;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $record */
        $record->fill(collect($data)->except(['role', 'status', 'created_by', 'updated_by'])->all());
        $record->forceFill([
            'role' => User::ROLE_STAFF,
            'status' => $data['status'],
            'updated_by' => $data['updated_by'],
        ])->save();

        return $record;
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label(trans('Update'))
            ->submit('save')
            ->keyBindings(['mod+s']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return trans('User updated successfully');
    }
}
