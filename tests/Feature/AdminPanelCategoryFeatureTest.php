<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\CategoryResource;
use App\Filament\Resources\CategoryResource\Pages\CreateCategory;
use App\Filament\Resources\CategoryResource\Pages\EditCategory;
use App\Filament\Resources\CategoryResource\Pages\ListCategories;
use App\Filament\Resources\CategoryResource\Pages\ViewCategory;
use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Filament\Tables\Actions\DeleteAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelCategoryFeatureTest extends TestCase
{
    private User $admin;

    private Category $seededCategory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutExceptionHandling();
        Storage::fake('public');
        $this->seed(CategorySeeder::class);
        $this->admin = $this->authenticatedUser(['role' => User::ROLE_ADMIN]);
        $this->seededCategory = Category::query()->firstOrFail();
    }

    public function test_category_list_and_seeded_category_details_can_be_viewed(): void
    {
        $this->get(CategoryResource::getUrl())->assertSuccessful();
        $this->get(CategoryResource::getUrl('view', ['record' => $this->seededCategory]))
            ->assertSuccessful()
            ->assertSee($this->seededCategory->name);

        Livewire::test(ListCategories::class)
            ->assertCanSeeTableRecords(Category::query()->get());
        Livewire::test(ViewCategory::class, ['record' => $this->seededCategory->slug])
            ->assertFormSet(['name' => $this->seededCategory->name]);
    }

    public function test_admin_can_edit_and_delete_a_seeded_category(): void
    {
        $newName = 'Pakistan Supporter Kits';

        Livewire::test(EditCategory::class, ['record' => $this->seededCategory->slug])
            ->fillForm([
                'name' => $newName,
                'description' => 'Supporter kits available across Pakistan.',
                'sort_order' => 7,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $updated = Category::query()->where('name', $newName)->firstOrFail();
        Livewire::test(ListCategories::class)
            ->callTableAction(DeleteAction::class, $updated);

        $this->assertDatabaseMissing('categories', ['id' => $updated->id]);
    }

    public function test_admin_can_create_view_edit_and_delete_a_new_category(): void
    {
        Livewire::test(CreateCategory::class)
            ->fillForm([
                'name' => 'Pakistan Training Wear',
                'description' => 'Training apparel for football supporters in Pakistan.',
                'sort_order' => 8,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::query()->where('name', 'Pakistan Training Wear')->firstOrFail();
        $this->assertSame($this->admin->id, $category->created_by);

        $this->get(CategoryResource::getUrl('view', ['record' => $category]))->assertSuccessful();
        $this->get(CategoryResource::getUrl('edit', ['record' => $category]))->assertSuccessful();

        Livewire::test(ListCategories::class)
            ->callTableAction(DeleteAction::class, $category);

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_staff_can_manage_owned_and_seeded_categories(): void
    {
        $staff = $this->authenticatedUser(['role' => User::ROLE_STAFF]);
        $owned = Category::query()->create([
            'name' => 'Staff Collection',
            'description' => 'Created by staff.',
            'is_active' => true,
            'sort_order' => 20,
            'created_by' => $staff->id,
        ]);

        $this->assertTrue($staff->can('update', $owned));
        $this->assertTrue($staff->can('delete', $owned));
        $this->assertTrue($staff->can('update', $this->seededCategory));
        $this->assertTrue($staff->can('delete', $this->seededCategory));
    }
}
