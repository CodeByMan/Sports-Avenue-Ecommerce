<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\BrandResource\Pages;
use App\Models\Brand;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BrandResource extends Resource
{
    use ImageHandler;

    protected static ?string $model = Brand::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordRouteKeyName = 'slug';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Brand details')
                ->schema([
                    TextInput::make('name')
                        ->required()
                        ->maxLength(100)
                        ->unique(Brand::class, 'name', ignoreRecord: true),
                    TextInput::make('country')->maxLength(100),
                    TextInput::make('website')->url()->maxLength(255),
                    TextInput::make('sort_order')->numeric()->minValue(0)->default(0),
                    Textarea::make('description')->rows(4)->columnSpanFull(),
                    FileUpload::make('image')
                        ->image()
                        ->disk('public')
                        ->directory('brands')
                        ->maxSize(3000)
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => self::setFileName(
                                $file->getClientOriginalName(),
                            ),
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
                ImageColumn::make('image')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn (Brand $record): string => $record->getImage()),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('country')->searchable()->sortable(),
                TextColumn::make('jerseys_count')
                    ->counts('jerseys')
                    ->label('Products')
                    ->sortable(),
                IconColumn::make('is_active')->boolean()->label('Active'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->label(trans('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(trans('Permanently delete this brand?'))
                    ->modalDescription(
                        trans('Products assigned to this brand will be kept and changed to unbranded.'),
                    )
                    ->successNotificationTitle(trans('Brand deleted permanently')),
            ])
            ->bulkActions([])
            ->defaultSort('sort_order');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBrands::route('/'),
            'create' => Pages\CreateBrand::route('/create'),
            'view' => Pages\ViewBrand::route('/{record:slug}'),
            'edit' => Pages\EditBrand::route('/{record:slug}/edit'),
        ];
    }
}
