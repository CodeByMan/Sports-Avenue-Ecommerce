<?php

declare(strict_types=1);

namespace App\Filament\Widgets;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentOrders extends BaseWidget
{
    protected static ?string $heading = 'Recent orders';

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()
                    ->with('user')
                    ->where('status', '!=', Order::STATUS_IN_CART)
                    ->latest('id')
            )
            ->columns([
                TextColumn::make('invoice_number')->label('Invoice')->searchable(),
                TextColumn::make('customer_name')
                    ->label('Customer')
                    ->getStateUsing(fn (Order $record): string => $record->customer_name ?: ($record->user?->name ?? '-'))
                    ->searchable(query: fn ($query, string $search) => $query->where(function ($query) use ($search): void {
                        $query->where('customer_name', 'like', "%{$search}%")
                            ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"));
                    })),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Order::STATUS_SUCCESS => 'success',
                        Order::STATUS_PENDING => 'warning',
                        Order::STATUS_REVIEW => 'primary',
                        default => 'danger',
                    }),
                TextColumn::make('total_price')->label('Total')->formatStateUsing(fn (int $state): string => currencyFormat($state))->sortable(),
                TextColumn::make('placed_at')->label('Placed')->since()->placeholder('-')->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-m-eye')
                    ->url(fn (Order $record): string => OrderResource::getUrl('view', ['record' => $record->invoice_number])),
            ])
            ->paginated([5, 10]);
    }
}
