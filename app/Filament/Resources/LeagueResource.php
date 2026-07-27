<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\LeagueResource\Pages;
use App\Models\League;
use App\Models\User;
use App\Traits\ImageHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class LeagueResource extends Resource
{
    use ImageHandler;

    protected static ?string $model = League::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordRouteKeyName = 'slug';

    public static function getModelLabel(): string
    {
        return trans('League');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('name')
                    ->label(trans('Name'))
                    ->required()
                    ->maxLength(75)
                    ->unique(League::class, 'name', ignoreRecord: true),
                TextInput::make('country')
                    ->label(trans('Country'))
                    ->required()
                    ->maxLength(75),
                FileUpload::make('image')
                    ->label(trans('Choose image'))
                    ->required()
                    ->image()
                    ->disk('public')
                    ->maxSize(2500)
                    ->directory('leagues')
                    ->getUploadedFileNameForStorageUsing(
                        fn (TemporaryUploadedFile $file): string => self::setFileName($file->getClientOriginalName()),
                    ),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(trans('Name'))
                    ->searchable(),

                TextColumn::make('country')
                    ->label(trans('Country'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->label(trans('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(trans('Permanently delete this league?'))
                    ->modalDescription(
                        fn (League $record): string => self::deleteModalDescription($record),
                    )
                    ->form(
                        fn (League $record): array => self::deleteActionForm($record),
                    )
                    ->action(
                        fn (League $record, array $data): bool => self::deleteWithReplacement($record, $data),
                    )
                    ->successNotificationTitle(trans('League deleted permanently')),
            ])
            ->defaultSort('created_at', 'desc');
    }

    /** @return array<int, Select> */
    public static function deleteActionForm(League $record): array
    {
        if (! $record->jerseys()->exists()) {
            return [];
        }

        return [
            Select::make('replacement_league_id')
                ->label(trans('Move products to'))
                ->options(
                    League::query()
                        ->whereKeyNot($record->getKey())
                        ->orderBy('name')
                        ->pluck('name', 'id')
                        ->all(),
                )
                ->required()
                ->searchable()
                ->preload()
                ->helperText(trans('Products must belong to another league before this league can be deleted.')),
        ];
    }

    public static function deleteModalDescription(League $record): string
    {
        return $record->jerseys()->exists()
            ? trans('Choose a replacement league. Existing products will be moved before deletion.')
            : trans('This action permanently removes the selected league.');
    }

    /** @param array<string, mixed> $data */
    public static function deleteWithReplacement(League $record, array $data): bool
    {
        return DB::transaction(function () use ($record, $data): bool {
            $record->refresh();

            if ($record->jerseys()->exists()) {
                $replacementId = (int) ($data['replacement_league_id'] ?? 0);
                $replacement = League::query()
                    ->whereKey($replacementId)
                    ->whereKeyNot($record->getKey())
                    ->first();

                if ($replacement === null) {
                    throw ValidationException::withMessages([
                        'replacement_league_id' => trans('Select a valid replacement league.'),
                    ]);
                }

                $record->jerseys()->update([
                    'league_id' => $replacement->getKey(),
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            }

            return (bool) $record->delete();
        }, 3);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeagues::route('/'),
            'create' => Pages\CreateLeague::route('/create'),
            'view' => Pages\ViewLeague::route('/{record:slug}'),
            'edit' => Pages\EditLeague::route('/{record:slug}/edit'),
        ];
    }

    public static function getUser(): User
    {
        return auth()->user();
    }
}
