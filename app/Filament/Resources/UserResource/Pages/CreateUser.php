<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password as PasswordBroker;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected static bool $canCreateAnother = false;

    protected static ?string $title = 'Create user';

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
        return array_merge(
            [$this->getCreateFormAction()],
            static::canCreateAnother() ? [$this->getCreateAnotherFormAction()] : [],
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['role'] = User::$roles[1];
        $data['status'] = User::STATUS_ACTIVE;
        $data['password'] = Hash::make(Str::random(64));
        $data['created_by'] = $this->getResource()::getUser()->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = new User;
        $user->fill(collect($data)->except(['role', 'status', 'created_by', 'updated_by'])->all());
        $user->forceFill([
            'role' => $data['role'],
            'status' => $data['status'],
            'created_by' => $data['created_by'],
        ])->save();

        return $user;
    }

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;
        $user->sendEmailVerificationNotification();
        $status = PasswordBroker::sendResetLink(['email' => $user->email]);

        if ($status !== PasswordBroker::RESET_LINK_SENT) {
            Log::warning('Staff account setup link could not be sent.', [
                'user_id' => $user->getKey(),
                'status' => $status,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return trans('User created successfully');
    }
}
