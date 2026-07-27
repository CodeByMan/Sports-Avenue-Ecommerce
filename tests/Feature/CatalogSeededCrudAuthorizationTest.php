<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Jersey;
use App\Models\League;
use App\Models\User;
use Database\Seeders\JerseySeeder;
use Tests\TestCase;

class CatalogSeededCrudAuthorizationTest extends TestCase
{
    public function test_admin_can_edit_and_delete_seeded_leagues_and_products(): void
    {
        $this->seed(JerseySeeder::class);
        $admin = $this->createUser(['role' => User::ROLE_ADMIN]);
        $league = League::query()->firstOrFail();
        $jersey = Jersey::query()->firstOrFail();

        $this->assertNull($league->created_by);
        $this->assertNull($jersey->created_by);
        $this->assertTrue($admin->can('view', $league));
        $this->assertTrue($admin->can('update', $league));
        $this->assertTrue($admin->can('delete', $league));
        $this->assertTrue($admin->can('view', $jersey));
        $this->assertTrue($admin->can('update', $jersey));
        $this->assertTrue($admin->can('delete', $jersey));
    }

    public function test_staff_can_manage_seeded_and_new_catalog_records(): void
    {
        $this->seed(JerseySeeder::class);
        $staff = $this->createUser(['role' => User::ROLE_STAFF]);
        $seededLeague = League::query()->firstOrFail();
        $seededJersey = Jersey::query()->firstOrFail();
        $ownedLeague = League::factory()->create(['created_by' => $staff->id]);
        $ownedJersey = Jersey::factory()->create([
            'league_id' => $ownedLeague->id,
            'brand_id' => $seededJersey->brand_id,
            'category_id' => $seededJersey->category_id,
            'created_by' => $staff->id,
        ]);

        $this->assertTrue($staff->can('update', $seededLeague));
        $this->assertTrue($staff->can('delete', $seededLeague));
        $this->assertTrue($staff->can('update', $seededJersey));
        $this->assertTrue($staff->can('delete', $seededJersey));
        $this->assertTrue($staff->can('update', $ownedLeague));
        $this->assertTrue($staff->can('delete', $ownedLeague));
        $this->assertTrue($staff->can('update', $ownedJersey));
        $this->assertTrue($staff->can('delete', $ownedJersey));
    }
}
