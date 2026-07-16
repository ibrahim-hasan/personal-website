<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->json('slug')->nullable()->after('slugs');
        });

        Schema::table('projects', function (Blueprint $table): void {
            $table->string('key', 80)->nullable()->unique()->after('id');
            $table->json('localized_slug')->nullable()->after('slug');
            $table->string('slug_ar', 190)->nullable()->unique()->after('localized_slug');
            $table->string('slug_en', 190)->nullable()->unique()->after('slug_ar');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->string('key', 80)->nullable()->unique()->after('id');
            $table->json('localized_slug')->nullable()->after('slug');
            $table->string('slug_ar', 190)->nullable()->unique()->after('localized_slug');
            $table->string('slug_en', 190)->nullable()->unique()->after('slug_ar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('articles', 'slug')) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->dropColumn('slug');
            });
        }

        $projectColumns = ['key', 'slug_ar', 'slug_en'];

        if (Schema::hasColumn('projects', 'localized_slug')) {
            $projectColumns[] = 'localized_slug';
        }

        Schema::table('projects', function (Blueprint $table) use ($projectColumns): void {
            $table->dropUnique(['key']);
            $table->dropUnique(['slug_ar']);
            $table->dropUnique(['slug_en']);
            $table->dropColumn($projectColumns);
        });

        $serviceColumns = ['key', 'slug_ar', 'slug_en'];

        if (Schema::hasColumn('services', 'localized_slug')) {
            $serviceColumns[] = 'localized_slug';
        }

        Schema::table('services', function (Blueprint $table) use ($serviceColumns): void {
            $table->dropUnique(['key']);
            $table->dropUnique(['slug_ar']);
            $table->dropUnique(['slug_en']);
            $table->dropColumn($serviceColumns);
        });
    }
};
