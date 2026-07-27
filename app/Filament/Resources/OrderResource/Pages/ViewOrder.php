<?php

declare(strict_types=1);

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use App\Services\Orders\OrderWorkflowService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ViewOrder extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected static ?string $title = 'Order details';

    protected static string $view = 'filament.resources.order-resource.pages.view-order';

    public ?Order $order = null;

    public ?string $invoiceNumber = null;

    public function mount(): void
    {
        parent::mount();

        $this->invoiceNumber = (string) request()->query('record', '');
        $this->refreshOrder();
    }

    protected function getTableQuery(): Builder
    {
        return OrderItem::query()->where('order_id', $this->order?->getKey());
    }

    public function getBreadcrumb(): ?string
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
            Action::make('updateWorkflowStatus')
                ->label(trans('Update status'))
                ->icon('heroicon-o-arrow-path-rounded-square')
                ->color('primary')
                ->modalHeading(trans('Update order status'))
                ->form([
                    Select::make('fulfillment_status')
                        ->label(trans('New status'))
                        ->options(function (): array {
                            if ($this->order === null) {
                                return [];
                            }

                            return collect(app(OrderWorkflowService::class)->allowedTransitions($this->order))
                                ->mapWithKeys(fn (string $status): array => [$status => Order::fulfillmentStatusLabel($status)])
                                ->all();
                        })
                        ->required()
                        ->native(false),
                    Textarea::make('note')
                        ->label(trans('Status note'))
                        ->rows(3)
                        ->maxLength(1000),
                ])
                ->action(function (array $data): void {
                    /** @var User $actor */
                    $actor = auth()->user();

                    app(OrderWorkflowService::class)->transition(
                        $this->order,
                        (string) $data['fulfillment_status'],
                        $actor,
                        $data['note'] ?? null,
                    );

                    $this->refreshOrder();
                })
                ->successNotificationTitle(trans('Order status updated successfully'))
                ->visible(fn (): bool => $this->order !== null
                    && app(OrderWorkflowService::class)->allowedTransitions($this->order) !== []),
            Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    protected function getTableBulkActions(): array
    {
        return [];
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label(trans('Image'))
                    ->getStateUsing(fn (OrderItem $record): string => $record->image_url),
                TextColumn::make('product_name')->label(trans('Name')),
                TextColumn::make('product_sku')->label(trans('SKU')),
                TextColumn::make('nameset')
                    ->getStateUsing(fn (OrderItem $record): string => namesetFormat($record->nameset)),
                TextColumn::make('size')->label(trans('Size')),
                TextColumn::make('quantity')
                    ->label(trans('Quantity'))
                    ->formatStateUsing(fn (int $state): string => "{$state} pcs"),
                TextColumn::make('unit_price')
                    ->label(trans('Price'))
                    ->formatStateUsing(fn (int $state): string => currencyFormat($state)),
                TextColumn::make('line_total')
                    ->label(trans('Total Price'))
                    ->formatStateUsing(fn (int $state): string => currencyFormat($state)),
            ])
            ->actions([])
            ->recordUrl(null)
            ->defaultSort('id', 'asc');
    }

    private function refreshOrder(): void
    {
        $this->order = Order::query()
            ->with([
                'user.city.province',
                'items',
                'statusHistories.actor',
            ])
            ->where('invoice_number', $this->invoiceNumber)
            ->where('status', '!=', Order::STATUS_IN_CART)
            ->firstOrFail();
    }
}
