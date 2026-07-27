<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\BrandResource;
use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\JerseyResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\StorefrontSettingResource;
use App\Filament\Resources\UserResource;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Jersey;
use App\Models\Order;
use App\Models\StorefrontSetting;
use App\Models\User;
use Tests\TestCase;

class StaffPanelPermissionTest extends TestCase
{
    public function test_active_staff_can_manage_catalog_and_order_workflow(): void
    {
        $staff = $this->authenticatedUser(['role' => User::ROLE_STAFF]);
        $brand = Brand::query()->create([
            'name' => 'Staff Brand',
            'country' => 'Pakistan',
            'is_active' => true,
        ]);
        $category = Category::query()->create([
            'name' => 'Staff Category',
            'is_active' => true,
        ]);
        $jersey = Jersey::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
        ]);
        $order = Order::factory()->create(['status' => Order::STATUS_PENDING]);

        $this->assertTrue($staff->can('viewAny', Brand::class));
        $this->assertTrue($staff->can('create', Brand::class));
        $this->assertTrue($staff->can('update', $brand));
        $this->assertTrue($staff->can('delete', $brand));

        $this->assertTrue($staff->can('viewAny', Category::class));
        $this->assertTrue($staff->can('update', $category));
        $this->assertTrue($staff->can('update', $jersey));
        $this->assertTrue($staff->can('update', $order));

        $this->assertTrue(BrandResource::canViewAny());
        $this->assertTrue(CategoryResource::canViewAny());
        $this->assertTrue(JerseyResource::canViewAny());
        $this->assertTrue(OrderResource::canViewAny());
    }

    public function test_staff_cannot_access_homepage_or_user_management(): void
    {
        $staff = $this->authenticatedUser(['role' => User::ROLE_STAFF]);

        $this->assertFalse($staff->can('viewAny', StorefrontSetting::class));
        $this->assertFalse(StorefrontSettingResource::canViewAny());
        $this->assertFalse(StorefrontSettingResource::shouldRegisterNavigation());
        $this->assertFalse(UserResource::canViewAny());
    }

    public function test_admin_can_access_restricted_management_modules(): void
    {
        $this->authenticatedUser(['role' => User::ROLE_ADMIN]);

        $this->assertTrue(StorefrontSettingResource::canViewAny());
        $this->assertTrue(StorefrontSettingResource::shouldRegisterNavigation());
        $this->assertTrue(UserResource::canViewAny());
    }
}
