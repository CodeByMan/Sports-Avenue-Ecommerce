<x-filament-panels::page>
    <div class="sa-notifications-page">
        <div class="sa-notifications-page__toolbar">
            <div>
                <p>{{ __('Review order and account activity from one place.') }}</p>
                <span>
                    {{ trans_choice(':count unread notification|:count unread notifications', $unreadCount, ['count' => $unreadCount]) }}
                </span>
            </div>

            @if ($unreadCount > 0)
                <button type="button" class="sa-notifications-page__mark-all" wire:click="markAllAsRead">
                    {{ __('Mark all as read') }}
                </button>
            @endif
        </div>

        <div class="sa-notifications-list">
            @forelse ($notifications as $notification)
                @php
                    $data = $notification->data;
                    $title = $data['title'] ?? __('Notification');
                    $body = $data['body'] ?? $data['message'] ?? __('A new notification is available.');
                    $hasDestination = filled($data['url'] ?? null)
                        || collect($data['actions'] ?? [])->contains(
                            fn (mixed $action): bool => is_array($action) && filled($action['url'] ?? null),
                        );
                @endphp

                <article
                    wire:key="notification-{{ $notification->id }}"
                    class="sa-notification-card {{ $notification->read_at === null ? 'sa-notification-card--unread' : '' }}"
                >
                    <div class="sa-notification-card__icon" aria-hidden="true">
                        <x-filament::icon icon="heroicon-o-bell" class="h-6 w-6" />
                    </div>

                    <div class="sa-notification-card__content">
                        <div class="sa-notification-card__heading">
                            <strong>{{ $title }}</strong>
                            @if ($notification->read_at === null)
                                <span>{{ __('New') }}</span>
                            @endif
                        </div>

                        <p>{{ $body }}</p>
                        <time datetime="{{ $notification->created_at?->toAtomString() }}">
                            {{ $notification->created_at?->diffForHumans() }}
                        </time>
                    </div>

                    <div class="sa-notification-card__actions">
                        @if ($hasDestination)
                            <button type="button" wire:click="openNotification('{{ $notification->id }}')">
                                {{ __('Open') }}
                            </button>
                        @endif

                        @if ($notification->read_at === null)
                            <button type="button" wire:click="markAsRead('{{ $notification->id }}')">
                                {{ __('Mark read') }}
                            </button>
                        @endif

                        <button
                            type="button"
                            class="sa-notification-card__delete"
                            wire:click="deleteNotification('{{ $notification->id }}')"
                            wire:confirm="{{ __('Delete this notification?') }}"
                        >
                            {{ __('Delete') }}
                        </button>
                    </div>
                </article>
            @empty
                <div class="sa-notifications-empty">
                    <x-filament::icon icon="heroicon-o-bell-slash" class="h-10 w-10" />
                    <h2>{{ __('No notifications yet') }}</h2>
                    <p>{{ __('New order and account notifications will appear here.') }}</p>
                </div>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div class="sa-notifications-pagination">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
