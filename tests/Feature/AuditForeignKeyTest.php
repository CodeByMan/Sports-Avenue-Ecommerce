<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Jersey;
use App\Models\League;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuditForeignKeyTest extends TestCase
{
    public function test_deleting_an_actor_nulls_catalog_audit_references(): void
    {
        $actor = User::factory()->create(['role' => User::ROLE_STAFF]);
        $league = League::factory()->create([
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
        $jersey = Jersey::factory()->create([
            'league_id' => $league->id,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);

        $actor->delete();

        $this->assertDatabaseHas('leagues', [
            'id' => $league->id,
            'created_by' => null,
            'updated_by' => null,
        ]);
        $this->assertDatabaseHas('jerseys', [
            'id' => $jersey->id,
            'created_by' => null,
            'updated_by' => null,
        ]);
    }

    public function test_sqlite_schema_uses_set_null_for_audit_foreign_keys(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('SQLite schema inspection is only used by the default test environment.');
        }

        foreach ([
            'users' => ['created_by', 'updated_by'],
            'leagues' => ['created_by', 'updated_by'],
            'jerseys' => ['created_by', 'updated_by'],
        ] as $table => $columns) {
            $foreignKeys = collect(DB::select("PRAGMA foreign_key_list({$table})"));

            foreach ($columns as $column) {
                $foreignKey = $foreignKeys->first(
                    fn (object $key): bool => $key->from === $column,
                );

                $this->assertNotNull($foreignKey, "Missing {$table}.{$column} foreign key.");
                $this->assertSame('users', $foreignKey->table);
                $this->assertSame('SET NULL', strtoupper($foreignKey->on_delete));
            }
        }
    }
}
