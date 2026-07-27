<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use App\Services\Users\AccountStatusService;
use Filament\Actions;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected static ?string $title = 'User details';

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
            Actions\EditAction::make()
                ->visible(fn (): bool => UserResource::canEdit($this->record)),

            $this->customerStatusAction(
                'suspendCustomer',
                User::STATUS_SUSPENDED,
                'Suspend',
                'warning',
                'Suspend this customer?',
            ),

            $this->customerStatusAction(
                'blockCustomer',
                User::STATUS_BLOCKED,
                'Block',
                'danger',
                'Block this customer?',
            ),

            Actions\Action::make('reactivateCustomer')
                ->label(trans('Reactivate'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading(trans('Reactivate this customer?'))
                ->action(function (): void {
                    app(AccountStatusService::class)->changeCustomerStatus(
                        $this->record,
                        User::STATUS_ACTIVE,
                        UserResource::getUser(),
                    );

                    $this->record->refresh();
                })
                ->successNotificationTitle(trans('Customer reactivated successfully'))
                ->visible(
                    fn (): bool => $this->record->role === User::ROLE_CUSTOMER
                        && in_array(
                            $this->record->status,
                            [User::STATUS_SUSPENDED, User::STATUS_BLOCKED],
                            true,
                        ),
                ),

            Actions\DeleteAction::make()
                ->label(trans('Delete'))
                ->requiresConfirmation()
                ->modalHeading(trans('Permanently delete this user?'))
                ->modalDescription(
                    trans('Users with order history and the last administrator cannot be deleted.'),
                )
                ->visible(fn (): bool => UserResource::canDelete($this->record))
                ->disabled(fn (): bool => $this->deletionBlocked()),

            Actions\Action::make('back')
                ->label(trans('Back'))
                ->color('gray')
                ->url($this->getResource()::getUrl()),
        ];
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->schema([
                    TextInput::make('name')->label(trans('Name')),
                    TextInput::make('username')->label(trans('Username')),
                    TextInput::make('email')->label(trans('Email')),
                    TextInput::make('phone')->label(trans('Phone')),
                    TextInput::make('role')->label(trans('Role')),
                    Textarea::make('address')->label(trans('Address')),
                    TextInput::make('status')->label(trans('Status')),
                    Textarea::make('status_reason')
                        ->label(trans('Restriction reason'))
                        ->visible(fn (?User $record): bool => filled($record?->status_reason))
                        ->columnSpanFull(),
                    TextInput::make('status_changed_at')
                        ->label(trans('Status changed at'))
                        ->formatStateUsing(
                            fn ($state): string => $state?->format('M j, Y g:i A') ?? '-',
                        ),
                    TextInput::make('created_by')
                        ->label(trans('Created by'))
                        ->formatStateUsing(
                            fn (?int $state = null): string => $state === null
                                ? ''
                                : (User::find($state)?->name ?? ''),
                        ),
                    TextInput::make('updated_by')
                        ->label(trans('Updated by'))
                        ->formatStateUsing(
                            fn (?int $state = null): string => $state === null
                                ? ''
                                : (User::find($state)?->name ?? ''),
                        ),
                ])
                ->columns(2),
        ]);
    }

    private function customerStatusAction(
        string $name,
        string $status,
        string $label,
        string $color,
        string $heading,
    ): Actions\Action {
        return Actions\Action::make($name)
            ->label(trans($label))
            ->icon(
                $status === User::STATUS_BLOCKED
                    ? 'heroicon-o-no-symbol'
                    : 'heroicon-o-pause-circle',
            )
            ->color($color)
            ->requiresConfirmation()
            ->modalHeading(trans($heading))
            ->form([
                Textarea::make('reason')
                    ->label(trans('Reason'))
                    ->required()
                    ->maxLength(1000)
                    ->rows(3),
            ])
            ->action(function (array $data) use ($status): void {
                app(AccountStatusService::class)->changeCustomerStatus(
                    $this->record,
                    $status,
                    UserResource::getUser(),
                    (string) $data['reason'],
                );

                $this->record->refresh();
            })
            ->successNotificationTitle(
                $status === User::STATUS_BLOCKED
                    ? trans('Customer blocked successfully')
                    : trans('Customer suspended successfully'),
            )
            ->visible(
                fn (): bool => $this->record->role === User::ROLE_CUSTOMER
                    && $this->record->status !== $status,
            );
    }

    private function deletionBlocked(): bool
    {
        if ($this->record->orders()->exists()) {
            return true;
        }

        return $this->record->role === User::ROLE_ADMIN
            && ! User::query()
                ->where('role', User::ROLE_ADMIN)
                ->whereKeyNot($this->record->getKey())
                ->exists();
    }
}
