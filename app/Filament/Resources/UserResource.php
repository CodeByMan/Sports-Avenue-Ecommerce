<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Admin Management';

    protected static ?string $navigationLabel = 'Users & Customers';

    protected static ?string $recordRouteKeyName = 'username';

    public static function canViewAny(): bool
    {
        return self::isAdmin();
    }

    public static function canCreate(): bool
    {
        return self::isAdmin();
    }

    public static function canView(Model $record): bool
    {
        return self::isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        return self::isAdmin()
            && $record instanceof User
            && $record->role === User::ROLE_STAFF;
    }

    public static function canDelete(Model $record): bool
    {
        return self::isAdmin()
            && $record instanceof User
            && $record->getKey() !== self::getUser()->getKey();
    }

    public static function getBreadcrumb(): string
    {
        return trans('Manage users');
    }

    public static function getNavigationLabel(): string
    {
        return trans('Users & Customers');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereKeyNot(self::getUser()->getKey());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Staff account')
                ->description('Create or update staff accounts. Customer restrictions are managed from the table actions.')
                ->schema([
                    TextInput::make('name')
                        ->label(trans('Name'))
                        ->required()
                        ->maxLength(90)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $username = str((string) $state)->slug('')->value();
                            $set('username', User::query()->where('username', $username)->exists()
                                ? $username.rand(11, 99)
                                : $username);
                        }),
                    TextInput::make('username')
                        ->label(trans('Username'))
                        ->required()
                        ->maxLength(35)
                        ->unique(User::class, 'username', ignoreRecord: true),
                    TextInput::make('email')
                        ->label(trans('Email'))
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(User::class, 'email', ignoreRecord: true),
                    TextInput::make('phone')
                        ->label(trans('Phone'))
                        ->tel()
                        ->required()
                        ->rule('regex:/^(?:\+92|0092|0)?3\d{9}$/')
                        ->unique(User::class, 'phone', ignoreRecord: true),
                    Textarea::make('address')
                        ->label(trans('Address'))
                        ->required()
                        ->minLength(10)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Radio::make('status')
                        ->label(trans('Status'))
                        ->options([
                            User::STATUS_ACTIVE => trans('Active'),
                            User::STATUS_INACTIVE => trans('Inactive'),
                        ])
                        ->in([User::STATUS_ACTIVE, User::STATUS_INACTIVE])
                        ->default(User::STATUS_ACTIVE)
                        ->required(),
                    Hidden::make('role')
                        ->default(User::ROLE_STAFF)
                        ->dehydrated(fn (string $operation): bool => $operation === 'create'),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(trans('Name'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label(trans('Email'))
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('role')
                    ->label(trans('Role'))
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ucfirst(strtolower($state))
                    )
                    ->color(fn (string $state): string => match ($state) {
                        User::ROLE_ADMIN => 'danger',
                        User::ROLE_STAFF => 'primary',
                        User::ROLE_CUSTOMER => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(
                        fn (string $state): string => ucfirst(strtolower($state))
                    )
                    ->color(fn (string $state): string => match ($state) {
                        User::STATUS_ACTIVE => 'success',
                        User::STATUS_SUSPENDED => 'warning',
                        User::STATUS_BLOCKED => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('status_reason')
                    ->label(trans('Restriction reason'))
                    ->limit(35)
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_STAFF => 'Staff',
                        User::ROLE_CUSTOMER => 'Customer',
                    ]),

                SelectFilter::make('status')
                    ->options([
                        User::STATUS_ACTIVE => trans('Active'),
                        User::STATUS_INACTIVE => trans('Inactive'),
                        User::STATUS_SUSPENDED => trans('Suspended'),
                        User::STATUS_BLOCKED => trans('Blocked'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make()
                    ->visible(
                        fn (User $record): bool => self::canEdit($record)
                    ),

                Tables\Actions\DeleteAction::make()
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
                        fn (User $record): bool => self::canDelete($record)
                    )
                    ->disabled(
                        fn (User $record): bool => self::deletionBlocked($record)
                    )
                    ->tooltip(
                        fn (User $record): ?string => self::deletionBlocked($record)
                                ? trans(
                                    'This user is protected by order history or administrator safety rules.'
                                )
                                : null
                    )
                    ->successNotificationTitle(
                        trans('User deleted permanently')
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record:username}'),
            'edit' => Pages\EditUser::route('/edit/{record:username}'),
        ];
    }

    public static function getUser(): User
    {
        return auth()->user();
    }

    private static function isAdmin(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isActive()
            && $user->role === User::ROLE_ADMIN
            && $user->hasVerifiedEmail();
    }

    private static function deletionBlocked(User $record): bool
    {
        if ($record->orders()->exists()) {
            return true;
        }

        return $record->role === User::ROLE_ADMIN
            && ! User::query()
                ->where('role', User::ROLE_ADMIN)
                ->whereKeyNot($record->getKey())
                ->exists();
    }
}
