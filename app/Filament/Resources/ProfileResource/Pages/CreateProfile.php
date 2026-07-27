<?php

declare(strict_types=1);

namespace App\Filament\Resources\ProfileResource\Pages;

use App\Filament\Resources\ProfileResource;
use App\Traits\ImageHandler;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class CreateProfile extends CreateRecord
{
    use ImageHandler;

    protected static string $resource = ProfileResource::class;

    protected static bool $canCreateAnother = false;

    private string $notificationMessage = 'Your profile updated successfully';

    private bool $emailChanged = false;

    private ?string $oldAvatarToDelete = null;

    protected function fillForm(): void
    {
        $this->callHook('beforeFill');
        $this->form->fill($this->getResource()::getUser()->toArray());
        $this->callHook('afterFill');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (! empty($data['new_password'])) {
            $data['password'] = Hash::make((string) $data['new_password']);
        }

        $user = $this->getResource()::getUser();
        $newAvatar = $data['avatar'] ?? null;

        if ($newAvatar !== $user->avatar && $user->avatar !== null) {
            $this->oldAvatarToDelete = $user->avatar;
        }

        if ($newAvatar === null) {
            $data['avatar'] = null;
        }

        unset($data['new_password']);

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = $this->getResource()::getUser();
        $this->emailChanged = isset($data['email'])
            && strtolower((string) $data['email']) !== strtolower($user->email);

        $user->fill($data);

        if ($this->emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($this->oldAvatarToDelete !== null) {
            $this->deleteImage($this->oldAvatarToDelete);
        }

        if ($this->emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        $sessionName = 'password_hash_'.auth()->getDefaultDriver();

        if (session()->has($sessionName) && isset($data['password'])) {
            session()->forget($sessionName);
            session()->put([$sessionName => $user->getAuthPassword()]);
            $this->notificationMessage = 'Your profile and password updated successfully';
        }

        return $user;
    }

    public function getBreadcrumb(): string
    {
        return '';
    }

    public function getTitle(): string
    {
        return $this->getResource()::getUser()->name;
    }

    protected function getFormActions(): array
    {
        return [$this->getCreateFormAction()];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(trans('Back to dashboard'))
                ->color('gray')
                ->url($this->getDefaultRoute()),
        ];
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(trans('Update'))
            ->submit('create')
            ->keyBindings(['mod+s']);
    }

    protected function getRedirectUrl(): string
    {
        return $this->emailChanged
            ? route('verification.notice')
            : $this->getDefaultRoute();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return trans($this->notificationMessage);
    }

    private function getDefaultRoute(): string
    {
        return route('filament.admin.pages.dashboard');
    }
}
