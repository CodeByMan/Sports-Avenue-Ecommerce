<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\JerseyResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Jersey;
use App\Models\League;
use App\Models\User;
use App\Traits\ImageHandler;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class JerseyResource extends Resource
{
    use ImageHandler;

    protected static ?string $model = Jersey::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?string $navigationLabel = 'Products';

    protected static ?string $pluralModelLabel = 'Products';

    protected static ?string $modelLabel = 'Product';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordRouteKeyName = 'slug';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Product identity')
                ->schema([
                    Select::make('league_id')
                        ->label('League')
                        ->relationship('league', 'name')
                        ->required()
                        ->exists(League::class, 'id')
                        ->searchable()
                        ->preload(),
                    Select::make('brand_id')
                        ->label('Brand')
                        ->relationship('brand', 'name')
                        ->default(fn (): ?int => Brand::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->value('id'))
                        ->exists(Brand::class, 'id')
                        ->searchable()
                        ->preload(),
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->default(fn (): ?int => Category::query()
                            ->where('is_active', true)
                            ->orderBy('sort_order')
                            ->value('id'))
                        ->exists(Category::class, 'id')
                        ->searchable()
                        ->preload(),
                    TextInput::make('sku')->required()->maxLength(64)->unique(Jersey::class, 'sku', ignoreRecord: true),
                    TextInput::make('name')->required()->maxLength(150)->unique(Jersey::class, 'name', ignoreRecord: true),
                    TextInput::make('type')->required()->maxLength(150),
                    Textarea::make('description')->rows(4)->columnSpanFull(),
                    Toggle::make('is_active')->default(true)->inline(false),
                    Toggle::make('is_featured')->default(false)->inline(false),
                ])
                ->columns(3),

            Section::make('Pricing and inventory')
                ->schema([
                    TextInput::make('weight')->numeric()->required()->minValue(1)->suffix('g'),
                    TextInput::make('price')->numeric()->required()->minValue(1)->prefix('Rs.'),
                    TextInput::make('price_nameset')->label('Nameset price')->numeric()->required()->minValue(0)->prefix('Rs.'),
                    TextInput::make('S')->label('Size S')->numeric()->required()->minValue(0)->default(0),
                    TextInput::make('M')->label('Size M')->numeric()->required()->minValue(0)->default(0),
                    TextInput::make('L')->label('Size L')->numeric()->required()->minValue(0)->default(0),
                    TextInput::make('XL')->label('Size XL')->numeric()->required()->minValue(0)->default(0),
                ])
                ->columns(4),

            Section::make('Product media')
                ->schema([
                    FileUpload::make('image')
                        ->label('Primary image')
                        ->required()
                        ->image()
                        ->disk('public')
                        ->directory('jerseys')
                        ->maxSize(4000)
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => self::setFileName($file->getClientOriginalName()),
                        ),
                    FileUpload::make('gallery')
                        ->label('Gallery images')
                        ->multiple()
                        ->reorderable()
                        ->maxFiles(3)
                        ->image()
                        ->disk('public')
                        ->directory('jerseys/gallery')
                        ->maxSize(4000)
                        ->afterStateHydrated(function (FileUpload $component, mixed $state, ?Jersey $record): void {
                            if ($record !== null && blank($state) && filled($record->gallery)) {
                                $component->state($record->gallery);
                            }
                        })
                        ->getUploadedFileNameForStorageUsing(
                            fn (TemporaryUploadedFile $file): string => self::setFileName($file->getClientOriginalName()),
                        ),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('brand.name')
                    ->label('Brand')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Category')
                    ->sortable(),

                TextColumn::make('league.name')
                    ->label('League')
                    ->sortable(),

                TextColumn::make('price')
                    ->formatStateUsing(
                        fn (int $state): string => currencyFormat($state)
                    )
                    ->sortable(),

                TextColumn::make('sold')
                    ->numeric()
                    ->sortable(),

                IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
            ])
            ->filters([
                Filter::make('league')
                    ->form([
                        Select::make('league')
                            ->label('League')
                            ->options(
                                fn (): array => League::query()
                                    ->orderBy('name')
                                    ->pluck('name', 'slug')
                                    ->all()
                            )
                            ->searchable()
                            ->preload(),
                    ])
                    ->query(
                        fn (Builder $query, array $data): Builder => $query->when(
                            filled($data['league'] ?? null),
                            fn (Builder $query): Builder => $query->hasLeague($data['league'])
                        )
                    ),

                SelectFilter::make('brand')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),

                Tables\Actions\EditAction::make(),

                Tables\Actions\DeleteAction::make()
                    ->label(trans('Delete'))
                    ->requiresConfirmation()
                    ->modalHeading(
                        trans('Permanently delete this product?')
                    )
                    ->modalDescription(
                        trans(
                            'The product will be removed permanently. Historical order snapshots will remain available.'
                        )
                    )
                    ->successNotificationTitle(
                        trans('Product deleted permanently')
                    ),
            ])
            ->defaultSort('sold', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJerseys::route('/'),
            'create' => Pages\CreateJersey::route('/create'),
            'view' => Pages\ViewJersey::route('/{record:slug}'),
            'edit' => Pages\EditJersey::route('/{record:slug}/edit'),
        ];
    }

    public static function getUser(): User
    {
        return auth()->user();
    }

    public static function mergeSizeFields(array $data): array
    {
        $data['brand_id'] ??= Brand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');
        $data['category_id'] ??= Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->value('id');

        $data['stock'] = array_combine(Jersey::$sizes, [$data['S'], $data['M'], $data['L'], $data['XL']]);

        foreach (Jersey::$sizes as $size) {
            unset($data[$size]);
        }

        return $data;
    }
}
