<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<int, string> */
    private array $tables = [
        'jerseys',
        'categories',
        'leagues',
        'users',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (
                ! Schema::hasTable($table)
                || ! Schema::hasColumn($table, 'deleted_at')
            ) {
                continue;
            }

            $updates = [
                'deleted_at' => null,
            ];

            if (Schema::hasColumn($table, 'deleted_by')) {
                $updates['deleted_by'] = null;
            }

            DB::table($table)
                ->whereNotNull('deleted_at')
                ->update($updates);

            Schema::table(
                $table,
                function (Blueprint $blueprint): void {
                    $blueprint->dropSoftDeletes();
                }
            );
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (
                ! Schema::hasTable($table)
                || Schema::hasColumn($table, 'deleted_at')
            ) {
                continue;
            }

            Schema::table(
                $table,
                function (Blueprint $blueprint): void {
                    $blueprint->softDeletes();
                }
            );
        }
    }
};
