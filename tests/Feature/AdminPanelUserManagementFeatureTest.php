<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages\CreateUser;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Models\User;
use App\Services\Users\AccountStatusService;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelUserManagementFeatureTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
        $this->admin = $this->authenticatedUser([
            'role' => User::ROLE_ADMIN,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_admin_can_open_user_management(): void
    {
        $this->get(UserResource::getUrl())
            ->assertSuccessful()
            ->assertSee('Users & Customers');
    }

    public function test_staff_cannot_open_user_management(): void
    {
        $this->withExceptionHandling();
        $staff = $this->createUser(['role' => User::ROLE_STAFF]);

        $this->actingAs($staff)
            ->get(UserResource::getUrl())
            ->assertForbidden();
    }

    public function test_admin_can_create_and_edit_staff(): void
    {
        $data = [
            'name' => 'QA Staff Member',
            'username' => 'qastaffmember',
            'email' => 'qa.staff@example.test',
            'phone' => '03009998877',
            'address' => 'Gulberg III, Lahore, Punjab',
            'status' => User::STATUS_ACTIVE,
            'role' => User::ROLE_STAFF,
        ];

        Livewire::test(CreateUser::class)
            ->fillForm($data)
            ->call('create')
            ->assertHasNoFormErrors();

        $staff = User::query()->where('email', $data['email'])->firstOrFail();

        $this->assertSame(User::ROLE_STAFF, $staff->role);

        Livewire::test(EditUser::class, ['record' => $staff->username])
            ->fillForm([
                'name' => 'QA Staff Updated',
                'username' => $staff->username,
                'email' => $staff->email,
                'phone' => $staff->phone,
                'address' => 'Johar Town, Lahore, Punjab',
                'status' => User::STATUS_INACTIVE,
                'role' => User::ROLE_STAFF,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('users', [
            'id' => $staff->id,
            'name' => 'QA Staff Updated',
            'status' => User::STATUS_INACTIVE,
        ]);
    }

    public function test_admin_can_permanently_delete_staff(): void
    {
        $staff = $this->createUser([
            'role' => User::ROLE_STAFF,
        ]);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $staff);

        $this->assertDatabaseMissing('users', [
            'id' => $staff->id,
        ]);
    }

    public function test_admin_can_permanently_delete_customer_without_orders(): void
    {
        $customer = $this->createUser([
            'role' => User::ROLE_CUSTOMER,
        ]);

        Livewire::test(ListUsers::class)
            ->callTableAction('delete', $customer);

        $this->assertDatabaseMissing('users', [
            'id' => $customer->id,
        ]);
    }

    public function test_admin_can_suspend_block_and_reactivate_customer(): void
    {
        $customer = $this->createUser([
            'role' => User::ROLE_CUSTOMER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $service = app(AccountStatusService::class);

        $customer = $service->changeCustomerStatus(
            $customer,
            User::STATUS_SUSPENDED,
            $this->admin,
            'Repeated checkout verification failures.',
        );

        $this->assertSame(
            User::STATUS_SUSPENDED,
            $customer->status,
        );

        $customer = $service->changeCustomerStatus(
            $customer,
            User::STATUS_BLOCKED,
            $this->admin,
            'Confirmed fraudulent activity.',
        );

        $this->assertSame(
            User::STATUS_BLOCKED,
            $customer->status,
        );

        $customer = $service->changeCustomerStatus(
            $customer,
            User::STATUS_ACTIVE,
            $this->admin,
        );

        $this->assertSame(
            User::STATUS_ACTIVE,
            $customer->status,
        );

        $this->assertNull($customer->status_reason);
    }

    public function test_last_administrator_cannot_be_deleted(): void
    {
        $this->expectException(\DomainException::class);

        $this->admin->delete();
    }
}
