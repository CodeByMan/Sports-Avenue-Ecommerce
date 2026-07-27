<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProfileResource\Pages;
use App\Models\User;
use App\Traits\ImageHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Illuminate\Validation\Rules\Password;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class ProfileResource extends Resource
{
    use ImageHandler;

    protected static ?string $model = User::class;

    protected static ?string $pluralModelLabel = 'Profile';

    protected static ?string $slug = 'profile';

    protected static bool $shouldRegisterNavigation = false;

    protected static bool $shouldSkipAuthorization = true;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
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
                                $username = str((string) $state)->slug('')->value();
                                $usernameExists = User::query()
                                    ->where('username', $username)
                                    ->whereKeyNot(self::getUser()->getKey())
                                    ->exists();

                                $set('username', $usernameExists ? $username.rand(11, 99) : $username);
                            }),
                        TextInput::make('username')
                            ->required()
                            ->string()
                            ->alphaDash()
                            ->maxLength(35)
                            ->unique(ignorable: self::getUser()),
                        TextInput::make('email')
                            ->required()
                            ->string()
                            ->email()
                            ->maxLength(255)
                            ->unique(ignorable: self::getUser()),
                        TextInput::make('phone')
                            ->label(trans('Phone'))
                            ->numeric()
                            ->minValue(1)
                            ->rules(['digits_between:11,13'])
                            ->nullable()
                            ->unique(ignorable: self::getUser()),
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\CreateProfile::route('/'),
        ];
    }

    public static function getUser(): User
    {
        return auth()->user();
    }
}
