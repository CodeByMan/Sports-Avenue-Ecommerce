<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Notifications\DatabaseNotification;
use Livewire\WithPagination;

class Notifications extends Page
{
    use WithPagination;

    protected static ?string $navigationIcon = 'heroicon-o-bell';

    protected static ?string $title = 'Notifications';

    protected static ?string $slug = 'notifications';

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.pages.notifications';

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User
            && $user->isActive()
            && in_array($user->role, [User::ROLE_ADMIN, User::ROLE_STAFF], true);
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'notifications' => auth()->user()
                ->notifications()
                ->latest('created_at')
                ->paginate(15),
            'unreadCount' => auth()->user()->unreadNotifications()->count(),
        ];
    }

    public function markAsRead(string $notificationId): void
    {
        $this->findNotification($notificationId)->markAsRead();
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    public function deleteNotification(string $notificationId): void
    {
        $this->findNotification($notificationId)->delete();
    }

    public function openNotification(string $notificationId): void
    {
        $notification = $this->findNotification($notificationId);
        $url = $this->notificationUrl($notification->data);

        $notification->markAsRead();

        if ($url !== null) {
            $this->redirect($url);
        }
    }

    private function findNotification(string $notificationId): DatabaseNotification
    {
        return auth()->user()
            ->notifications()
            ->whereKey($notificationId)
            ->firstOrFail();
    }

    /** @param array<string, mixed> $data */
    private function notificationUrl(array $data): ?string
    {
        $url = $data['url'] ?? null;

        if (! is_string($url) || $url === '') {
            foreach (($data['actions'] ?? []) as $action) {
                if (is_array($action) && is_string($action['url'] ?? null) && $action['url'] !== '') {
                    $url = $action['url'];
                    break;
                }
            }
        }

        if (! is_string($url) || $url === '') {
            return null;
        }

        return str_starts_with($url, '/') || str_starts_with($url, url('/'))
            ? $url
            : null;
    }
}
