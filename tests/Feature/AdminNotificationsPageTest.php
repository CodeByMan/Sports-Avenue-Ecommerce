<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Pages\Notifications;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminNotificationsPageTest extends TestCase
{
    public function test_staff_can_open_notifications_as_a_full_dashboard_page(): void
    {
        $staff = $this->authenticatedUser(['role' => User::ROLE_STAFF]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $notification = $staff->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'tests.notification',
            'data' => [
                'title' => 'New test order',
                'body' => 'A test order is ready for review.',
                'url' => '/admin/orders',
            ],
        ]);

        $this->get(Notifications::getUrl(panel: 'admin'))
            ->assertOk()
            ->assertSeeText('Notifications')
            ->assertSeeText('New test order')
            ->assertSeeText('A test order is ready for review.');

        Livewire::test(Notifications::class)
            ->call('markAsRead', $notification->id);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_customer_cannot_access_the_admin_notifications_page(): void
    {
        $customer = $this->authenticatedUser(['role' => User::ROLE_CUSTOMER]);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        $this->assertFalse(Notifications::canAccess());
        $this->assertSame(User::ROLE_CUSTOMER, $customer->role);
    }
}
