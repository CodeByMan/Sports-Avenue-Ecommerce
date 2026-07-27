<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->hardenProviderIdentifiers();

        $driver = DB::getDriverName();
        if (! in_array($driver, ['mysql', 'mariadb', 'pgsql'], true)) {
            return;
        }

        foreach ([
            'users' => ['created_by', 'updated_by'],
            'leagues' => ['created_by', 'updated_by', 'deleted_by'],
            'jerseys' => ['created_by', 'updated_by', 'deleted_by'],
        ] as $table => $columns) {
            foreach ($columns as $column) {
                $this->hardenAuditReference($table, $column, $driver);
            }
        }
    }

    public function down(): void
    {
        // Data-integrity hardening is intentionally irreversible.
    }

    private function hardenProviderIdentifiers(): void
    {
        foreach (['provinces', 'cities'] as $table) {
            if (! Schema::hasColumn($table, 'provider_id')) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->unsignedInteger('provider_id')->nullable()->after('id');
                });
            }

            DB::table($table)
                ->whereNull('provider_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table): void {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update(['provider_id' => $row->id]);
                    }
                });

            $driver = DB::getDriverName();
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `provider_id` INT UNSIGNED NOT NULL");
            } elseif ($driver === 'pgsql') {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN provider_id SET NOT NULL");
            }

            if (! $this->hasUniqueIndex($table, 'provider_id', $driver)) {
                Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                    $blueprint->unique('provider_id', "{$table}_provider_id_unique");
                });
            }
        }
    }

    private function hardenAuditReference(string $table, string $column, string $driver): void
    {
        if (! Schema::hasColumn($table, $column)) {
            return;
        }

        if (Schema::getColumnType($table, $column) !== 'bigint') {
            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` BIGINT UNSIGNED NULL");
            } else {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} TYPE BIGINT USING {$column}::BIGINT");
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$column} DROP NOT NULL");
            }
        }

        DB::table($table)
            ->whereNotNull($column)
            ->whereNotExists(function ($query) use ($table, $column): void {
                $query->selectRaw('1')
                    ->from('users')
                    ->whereColumn('users.id', $table.'.'.$column);
            })
            ->update([$column => null]);

        $foreignKey = $this->auditForeignKey($table, $column, $driver);

        if ($foreignKey !== null && strtoupper($foreignKey['delete_rule']) === 'SET NULL') {
            return;
        }

        if ($foreignKey !== null) {
            $constraint = $foreignKey['constraint_name'];

            if (in_array($driver, ['mysql', 'mariadb'], true)) {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$constraint}`");
            } else {
                DB::statement("ALTER TABLE {$table} DROP CONSTRAINT {$constraint}");
            }
        }

        Schema::table($table, function (Blueprint $blueprint) use ($column): void {
            $blueprint->foreign($column)->references('id')->on('users')->nullOnDelete();
        });
    }

    /** @return array{constraint_name:string, delete_rule:string}|null */
    private function auditForeignKey(string $table, string $column, string $driver): ?array
    {
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = DB::table('information_schema.KEY_COLUMN_USAGE as kcu')
                ->join('information_schema.REFERENTIAL_CONSTRAINTS as rc', function ($join): void {
                    $join->on('rc.CONSTRAINT_SCHEMA', '=', 'kcu.CONSTRAINT_SCHEMA')
                        ->on('rc.CONSTRAINT_NAME', '=', 'kcu.CONSTRAINT_NAME')
                        ->on('rc.TABLE_NAME', '=', 'kcu.TABLE_NAME');
                })
                ->where('kcu.CONSTRAINT_SCHEMA', DB::getDatabaseName())
                ->where('kcu.TABLE_NAME', $table)
                ->where('kcu.COLUMN_NAME', $column)
                ->where('kcu.REFERENCED_TABLE_NAME', 'users')
                ->select(['kcu.CONSTRAINT_NAME as constraint_name', 'rc.DELETE_RULE as delete_rule'])
                ->first();

            return $row === null ? null : (array) $row;
        }

        $row = DB::selectOne(
            <<<'SQL'
                SELECT c.conname AS constraint_name,
                       CASE c.confdeltype
                           WHEN 'n' THEN 'SET NULL'
                           WHEN 'c' THEN 'CASCADE'
                           WHEN 'r' THEN 'RESTRICT'
                           WHEN 'a' THEN 'NO ACTION'
                           ELSE 'OTHER'
                       END AS delete_rule
                FROM pg_constraint c
                JOIN pg_class source_table ON source_table.oid = c.conrelid
                JOIN pg_attribute source_column
                  ON source_column.attrelid = source_table.oid
                 AND source_column.attnum = ANY (c.conkey)
                JOIN pg_class target_table ON target_table.oid = c.confrelid
                WHERE c.contype = 'f'
                  AND source_table.relname = ?
                  AND source_column.attname = ?
                  AND target_table.relname = 'users'
                LIMIT 1
                SQL,
            [$table, $column],
        );

        return $row === null ? null : (array) $row;
    }

    private function hasUniqueIndex(string $table, string $column, string $driver): bool
    {
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', $table)
                ->where('COLUMN_NAME', $column)
                ->where('NON_UNIQUE', 0)
                ->exists();
        }

        if ($driver === 'pgsql') {
            return DB::table('pg_indexes')
                ->where('tablename', $table)
                ->whereRaw('indexdef ILIKE ? AND indexdef ILIKE ?', ['%UNIQUE%', '%('.$column.')%'])
                ->exists();
        }

        return true;
    }
};
