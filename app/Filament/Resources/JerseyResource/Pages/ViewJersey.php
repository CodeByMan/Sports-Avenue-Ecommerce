<?php

declare(strict_types=1);

namespace App\Filament\Resources\JerseyResource\Pages;

use App\Filament\Resources\JerseyResource;
use App\Models\League;
use App\Models\User;
use Filament\Actions;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewJersey extends ViewRecord
{
    protected static string $resource = JerseyResource::class;

    protected static ?string $title = 'Product details';

    public function getBreadcrumb(): string
    {
        return trans(self::$title);
    }

    public function getTitle(): string
    {
        return trans(self::$title);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),

            Actions\DeleteAction::make()
                ->label(trans('Delete'))
                ->requiresConfirmation()
                ->modalHeading(
                    trans('Permanently delete this product?')
                )
                ->modalDescription(
                    trans('This action cannot be undone.')
                ),

            Actions\Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Product identity')
                ->schema([
                    TextInput::make('league_id')
                        ->label(trans('League'))
                        ->formatStateUsing(fn (?int $state): string => $state === null ? '' : (League::find($state)?->name ?? '')),
                    Select::make('brand_id')
                        ->label('Brand')
                        ->relationship('brand', 'name')
                        ->disabled(),
                    Select::make('category_id')
                        ->label('Category')
                        ->relationship('category', 'name')
                        ->disabled(),
                    TextInput::make('sku')->label(trans('SKU')),
                    TextInput::make('name')->label(trans('Name')),
                    TextInput::make('type')->label(trans('Type')),
                    Textarea::make('description')->rows(4)->columnSpanFull(),
                    Toggle::make('is_active')->label('Active')->disabled(),
                    Toggle::make('is_featured')->label('Featured')->disabled(),
                ])
                ->columns(3),

            Section::make('Pricing and inventory')
                ->schema([
                    TextInput::make('weight')
                        ->label(trans('Weight'))
                        ->formatStateUsing(fn (?int $state): string => $state === null ? '' : $state.' Gram'),
                    TextInput::make('price')
                        ->label(trans('Price'))
                        ->formatStateUsing(fn (?int $state): string => $state === null ? '' : currencyFormat($state)),
                    TextInput::make('price_nameset')
                        ->label(trans('Nameset price'))
                        ->formatStateUsing(fn (?int $state): string => $state === null ? '' : currencyFormat($state)),
                    TextInput::make('stock')
                        ->label(trans('Stock'))
                        ->formatStateUsing(function (?array $state): string {
                            if ($state === null) {
                                return '';
                            }

                            return collect($state)
                                ->map(fn (mixed $stock, string $size): string => $size.' : '.$stock)
                                ->implode(',  ');
                        }),
                    TextInput::make('sold')
                        ->label(trans('Sold'))
                        ->formatStateUsing(fn (?int $state): string => $state === null ? '' : $state.' pcs'),
                    TextInput::make('created_by')
                        ->label(trans('Created by'))
                        ->formatStateUsing(fn (?int $state): string => $state === null ? '' : (User::find($state)?->name ?? '')),
                ])
                ->columns(3),

            Section::make('Product media')
                ->schema([
                    FileUpload::make('image')
                        ->label(trans('Image'))
                        ->disk('public')
                        ->disabled(),
                ]),
        ]);
    }
}
