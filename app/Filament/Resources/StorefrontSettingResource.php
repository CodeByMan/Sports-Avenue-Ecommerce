<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StorefrontSettingResource\Pages;
use App\Models\StorefrontSetting;
use App\Models\User;
use App\Traits\ImageHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class StorefrontSettingResource extends Resource
{
    use ImageHandler;

    protected static ?string $model = StorefrontSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationGroup = 'Storefront Management';

    protected static ?string $navigationLabel = 'Homepage';

    protected static ?string $pluralModelLabel = 'Homepage settings';

    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === User::ROLE_ADMIN
            && auth()->user()?->isActive();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Hero section')
                ->description('Content displayed below the fixed storefront navigation.')
                ->schema([
                    TextInput::make('name')->required()->maxLength(100),
                    TextInput::make('hero_eyebrow')->maxLength(100),
                    TextInput::make('hero_title')->required()->maxLength(150),
                    Textarea::make('hero_subtitle')->rows(3)->columnSpanFull(),
                    TextInput::make('hero_cta_label')->maxLength(60),
                    TextInput::make('hero_cta_url')->maxLength(255)->placeholder('/jerseys'),
                    FileUpload::make('hero_image')
                        ->image()
                        ->disk('public')
                        ->directory('storefront')
                        ->imageEditor()
                        ->maxSize(6000)
                        ->columnSpanFull()
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => self::setFileName($file->getClientOriginalName()),
                        ),
                    Toggle::make('is_active')->default(true)->inline(false),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                TextColumn::make('hero_title')->label('Hero title')->searchable(),
                TextColumn::make('hero_cta_label')->label('CTA'),
                IconColumn::make('is_active')->boolean()->label('Active'),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStorefrontSettings::route('/'),
            'create' => Pages\CreateStorefrontSetting::route('/create'),
            'view' => Pages\ViewStorefrontSetting::route('/{record}'),
            'edit' => Pages\EditStorefrontSetting::route('/{record}/edit'),
        ];
    }
}
