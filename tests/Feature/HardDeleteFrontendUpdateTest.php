<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HardDeleteFrontendUpdateTest extends TestCase
{
    public function test_backend_tables_do_not_have_soft_delete_or_deleted_by_columns(): void
    {
        foreach (['brands', 'categories', 'leagues', 'jerseys', 'users'] as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'deleted_at'),
                "{$table}.deleted_at should not exist.",
            );
        }

        foreach (['brands', 'categories', 'leagues', 'jerseys'] as $table) {
            $this->assertFalse(
                Schema::hasColumn($table, 'deleted_by'),
                "{$table}.deleted_by should not exist.",
            );
        }
    }

    public function test_backend_resources_only_expose_standard_delete_actions(): void
    {
        foreach ([
            'BrandResource.php',
            'CategoryResource.php',
            'JerseyResource.php',
            'LeagueResource.php',
            'UserResource.php',
        ] as $resource) {
            $contents = file_get_contents(app_path("Filament/Resources/{$resource}"));

            $this->assertStringNotContainsString('TrashedFilter', $contents);
            $this->assertStringNotContainsString('RestoreAction', $contents);
            $this->assertStringNotContainsString('ForceDeleteAction', $contents);
            $this->assertStringNotContainsString('Move to trash', $contents);
        }
    }

    public function test_backend_models_do_not_use_soft_deletes_or_deleted_by(): void
    {
        foreach (['Brand', 'Category', 'Jersey', 'League', 'User'] as $model) {
            $contents = file_get_contents(app_path("Models/{$model}.php"));

            $this->assertStringNotContainsString('SoftDeletes', $contents);
            $this->assertStringNotContainsString('deleted_by', $contents);
        }
    }

    public function test_google_identity_column_is_removed(): void
    {
        $this->assertFalse(Schema::hasColumn('users', 'google_id'));
    }
}
