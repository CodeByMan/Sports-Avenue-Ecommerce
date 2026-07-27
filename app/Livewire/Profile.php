<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\User;
use App\Traits\ImageHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\Features\SupportRedirects\Redirector;

class Profile extends Component implements HasForms
{
    use ImageHandler, InteractsWithForms;

    public User $user;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->user = auth()->user();

        $this->form->fill([
            'name' => $this->user->name,
            'username' => $this->user->username,
            'email' => $this->user->email,
            'phone' => $this->user->phone,
            'address' => $this->user->address,
            'avatar' => $this->user->avatar,

        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make(trans('Profile'))
                    ->description(trans('Update profile form'))
                    ->schema([
                        TextInput::make('name')
                            ->label(trans('Name'))
                            ->required()
                            ->string()
                            ->maxLength(90)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, ?string $state): void {
                                $set('username', function () use ($state): string {
                                    $state = str((string) $state)->slug('')->value();
                                    $usernameExists = User::query()
                                        ->where('username', $state)
                                        ->whereKeyNot($this->user->getKey())
                                        ->exists();

                                    return $usernameExists
                                        ? $state.rand(11, 99)
                                        : $state;
                                });
                            }),
                        TextInput::make('username')
                            ->required()
                            ->string()
                            ->alphaDash()
                            ->maxLength(35)
                            ->unique(ignorable: $this->user),
                        TextInput::make('email')
                            ->required()
                            ->string()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignorable: $this->user),
                        TextInput::make('phone')
                            ->label(trans('Phone'))
                            ->tel()
                            ->rules(['nullable', 'regex:/^\d{11,13}$/'])
                            ->nullable()
                            ->unique(ignorable: $this->user),
                        Textarea::make('address')
                            ->label(trans('Address'))
                            ->nullable(),
                        FileUpload::make('avatar')
                            ->label(trans('Profile Picture'))
                            ->image()
                            ->maxSize(2500)
                            ->directory(User::$directory)
                            ->getUploadedFileNameForStorageUsing(
                                fn (TemporaryUploadedFile $file): string => self::setFileName($file->getClientOriginalName())
                            ),
                    ])
                    ->columns(2)
                    ->collapsible(),
                Section::make('Password')
                    ->label(trans('Change password form'))
                    ->schema([
                        TextInput::make('current_password')
                            ->label(trans('Current password'))
                            ->password()
                            ->requiredWith('new_password')
                            ->rules(['current_password'])
                            ->dehydrated(false),
                        TextInput::make('new_password')
                            ->label(trans('New password'))
                            ->password()
                            ->requiredWith('current_password')
                            ->different('current_password')
                            ->rules(['confirmed', Password::min(10)->numbers()->symbols()]),
                        TextInput::make('new_password_confirmation')
                            ->label(trans('Password confirmation'))
                            ->password()
                            ->requiredWith('new_password')
                            ->dehydrated(false),
                    ])
                    ->collapsible(),
            ])
            ->statePath('data')
            ->model($this->user);
    }

    public function mutateFormDataBeforeSave(array $formData): array
    {
        if (! empty($formData['new_password'])) {
            $formData['password'] = Hash::make((string) $formData['new_password']);
        }

        unset($formData['new_password']);

        return $formData;
    }

    public function save(): Redirector
    {
        $data = $this->mutateFormDataBeforeSave($this->form->getState());
        $oldAvatar = $this->user->avatar;
        $newAvatar = $data['avatar'] ?? null;
        $data['avatar'] = $newAvatar ?: null;

        $emailChanged = isset($data['email'])
            && strtolower((string) $data['email']) !== strtolower((string) $this->user->email);

        $this->user->fill($data);

        if ($emailChanged) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        if ($oldAvatar !== null && $oldAvatar !== $this->user->avatar) {
            $this->deleteImage($oldAvatar);
        }

        if ($emailChanged) {
            $this->user->sendEmailVerificationNotification();
        }

        $redirect = $emailChanged ? to_route('verification.notice') : to_route('home');

        if ($this->user->wasChanged()) {
            $status = isset($data['password'])
                ? trans('Your profile and password successfully updated')
                : trans('Your profile successfully updated');

            $redirect->with('status', $status);
        }

        return $redirect;
    }

    public function render(): View
    {
        return view('livewire.profile');
    }
}
