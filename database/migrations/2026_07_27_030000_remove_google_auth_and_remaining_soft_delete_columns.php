<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'google_id')) {
            if (Schema::hasIndex('users', 'users_google_id_unique')) {
                Schema::table('users', function (Blueprint $table): void {
                    $table->dropUnique('users_google_id_unique');
                });
            }

            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('google_id');
            });
        }

        foreach (['brands', 'categories', 'leagues', 'jerseys'] as $tableName) {
            if (Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->dropConstrainedForeignId('deleted_by');
                });
            }
        }

        if (Schema::hasColumn('brands', 'deleted_at')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->dropSoftDeletes();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'google_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('google_id')->nullable()->unique()->after('id');
            });
        }

        foreach (['brands', 'categories', 'leagues', 'jerseys'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'deleted_by')) {
                Schema::table($tableName, function (Blueprint $table): void {
                    $table->foreignId('deleted_by')
                        ->nullable()
                        ->constrained('users')
                        ->nullOnDelete();
                });
            }
        }

        if (! Schema::hasColumn('brands', 'deleted_at')) {
            Schema::table('brands', function (Blueprint $table): void {
                $table->softDeletes();
            });
        }
    }
};
