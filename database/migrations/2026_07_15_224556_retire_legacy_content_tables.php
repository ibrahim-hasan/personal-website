<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('media')) {
            Media::query()
                ->whereIn('model_type', [
                    'App\\Models\\Author',
                    'App\\Models\\Guide',
                    'App\\Models\\IntellectualLibrary',
                ])
                ->eachById(function (Media $media): void {
                    $media->delete();
                });
        }

        if (Schema::hasTable('settings')) {
            DB::table('settings')
                ->where('group', 'guide')
                ->orWhere('group', 'stats')
                ->orWhere(function ($query): void {
                    $query
                        ->where('group', 'home')
                        ->where('key', 'home_layers');
                })
                ->delete();
        }

        $legacyResources = [
            'authors',
            'guides',
            'guide_downloaders',
            'intellectual_libraries',
            'newsletters',
            'tags',
        ];
        $permissionActions = [
            'view_any',
            'view',
            'create',
            'update',
            'delete',
            'restore',
            'force_delete',
        ];
        $legacyPermissions = [];

        foreach ($legacyResources as $resource) {
            foreach ($permissionActions as $action) {
                $legacyPermissions[] = "{$action} {$resource}";
            }
        }

        $permissionsTable = config('permission.table_names.permissions', 'permissions');

        if (Schema::hasTable($permissionsTable)) {
            DB::table($permissionsTable)
                ->whereIn('name', $legacyPermissions)
                ->delete();
        }

        Schema::dropIfExists('taggables');
        Schema::dropIfExists('guide_downloaders');
        Schema::dropIfExists('guides');
        Schema::dropIfExists('intellectual_libraries');
        Schema::dropIfExists('authors');
        Schema::dropIfExists('newsletters');
        Schema::dropIfExists('tags');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'This migration permanently retires legacy content. Restore a database backup before rolling back beyond it.',
        );
    }
};
