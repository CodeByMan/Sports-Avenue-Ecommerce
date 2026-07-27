<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use App\Models\User;
use App\Services\Orders\OrderWorkflowService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationGroup = 'Staff Management';

    protected static ?int $navigationSort = 5;

    protected static ?string $recordRouteKeyName = 'invoice_number';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label(trans('Invoice Number'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('customer_name')
                    ->label(trans('Customer'))
                    ->getStateUsing(fn (Order $record): string => $record->customer_name ?: ($record->user?->name ?? '-'))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where(function (Builder $query) use ($search): void {
                        $query->where('customer_name', 'like', "%{$search}%")
                            ->orWhereHas('user', fn (Builder $userQuery): Builder => $userQuery->where('name', 'like', "%{$search}%"));
                    })),
                TextColumn::make('fulfillment_status')
                    ->label(trans('Order status'))
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => Order::fulfillmentStatusLabel($state))
                    ->color(fn (?string $state): string => Order::fulfillmentStatusColor($state))
                    ->sortable(),
                TextColumn::make('status')
                    ->label(trans('Payment status'))
                    ->badge()
                    ->formatStateUsing(fn (Order $record): string => $record->payment_status_label)
                    ->color(fn (Order $record): string => $record->payment_status_color),
                TextColumn::make('payment_method')
                    ->label(trans('Payment method'))
                    ->formatStateUsing(fn (Order $record): string => $record->payment_method_label),
                TextColumn::make('total_price')
                    ->label(trans('Total price'))
                    ->formatStateUsing(fn (int $state): string => currencyFormat($state))
                    ->sortable(),
                TextColumn::make('placed_at')
                    ->label(trans('Placed at'))
                    ->dateTime()
                    ->placeholder('-')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('fulfillment_status')
                    ->label(trans('Order status'))
                    ->options(Order::fulfillmentStatusOptions()),
                SelectFilter::make('status')
                    ->label(trans('Payment status'))
                    ->options([
                        Order::STATUS_PENDING => trans('Pending'),
                        Order::STATUS_SUCCESS => trans('Paid'),
                        Order::STATUS_FAILED => trans('Failed'),
                        Order::STATUS_REVIEW => trans('Payment review'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                self::workflowAction(),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function workflowAction(): Tables\Actions\Action
    {
        return Tables\Actions\Action::make('updateWorkflowStatus')
            ->label(trans('Update status'))
            ->icon('heroicon-o-arrow-path-rounded-square')
            ->color('primary')
            ->modalHeading(trans('Update order status'))
            ->form([
                Select::make('fulfillment_status')
                    ->label(trans('New status'))
                    ->options(fn (Order $record): array => collect(app(OrderWorkflowService::class)->allowedTransitions($record))
                        ->mapWithKeys(fn (string $status): array => [$status => Order::fulfillmentStatusLabel($status)])
                        ->all())
                    ->required()
                    ->native(false),
                Textarea::make('note')
                    ->label(trans('Status note'))
                    ->rows(3)
                    ->maxLength(1000),
            ])
            ->action(function (Order $record, array $data): void {
                /** @var User $actor */
                $actor = auth()->user();

                app(OrderWorkflowService::class)->transition(
                    $record,
                    (string) $data['fulfillment_status'],
                    $actor,
                    $data['note'] ?? null,
                );
            })
            ->successNotificationTitle(trans('Order status updated successfully'))
            ->visible(fn (Order $record): bool => app(OrderWorkflowService::class)->allowedTransitions($record) !== []);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('user')
            ->where('status', '!=', Order::STATUS_IN_CART);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/detail'),
        ];
    }
}
