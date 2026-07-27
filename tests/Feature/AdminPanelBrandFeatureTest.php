<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\BrandResource\Pages\ListBrands;
use App\Models\Brand;
use App\Models\Jersey;
use App\Models\League;
use App\Models\User;
use Filament\Tables\Actions\DeleteAction;
use Livewire\Livewire;
use Tests\TestCase;

class AdminPanelBrandFeatureTest extends TestCase
{
    public function test_deleting_a_brand_keeps_products_and_clears_their_brand_assignment(): void
    {
        $admin = $this->authenticatedUser(['role' => User::ROLE_ADMIN]);
        $league = League::factory()->create(['created_by' => $admin->id]);
        $brand = Brand::query()->create([
            'name' => 'Temporary Brand',
            'country' => 'Pakistan',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);
        $jersey = Jersey::factory()->create([
            'league_id' => $league->id,
            'brand_id' => $brand->id,
            'created_by' => $admin->id,
        ]);

        Livewire::test(ListBrands::class)
            ->callTableAction(DeleteAction::class, $brand);

        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
        $this->assertDatabaseHas('jerseys', [
            'id' => $jersey->id,
            'brand_id' => null,
        ]);
    }
}
