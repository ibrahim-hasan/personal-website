<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('services') && Schema::hasColumn('services', 'slug')) {
            DB::table('services')
                ->where('slug', 'like', 'legacy-service-%')
                ->delete();

            Schema::table('services', function (Blueprint $table): void {
                $table->string('slug', 80)->nullable(false)->change();
            });
        }

        if (! Schema::hasColumn('articles', 'slug_ar')) {
            Schema::table('articles', function (Blueprint $table): void {
                $table->string('slug_ar', 190)->nullable()->unique()->after('slugs');
                $table->string('slug_en', 190)->nullable()->unique()->after('slug_ar');
            });
        }

        DB::table('articles')
            ->select(['id', 'slugs'])
            ->orderBy('id')
            ->each(function (object $article): void {
                $slugs = json_decode((string) $article->slugs, true, flags: JSON_THROW_ON_ERROR);
                $slugAr = trim((string) ($slugs['ar'] ?? ''));
                $slugEn = trim((string) ($slugs['en'] ?? ''));

                if ($slugAr === '' || $slugEn === '') {
                    throw new RuntimeException("Article [{$article->id}] is missing a localized slug.");
                }

                DB::table('articles')
                    ->where('id', $article->id)
                    ->update([
                        'slug_ar' => $slugAr,
                        'slug_en' => $slugEn,
                    ]);
            });

        Schema::table('articles', function (Blueprint $table): void {
            $table->string('slug_ar', 190)->nullable(false)->change();
            $table->string('slug_en', 190)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        throw new RuntimeException(
            'The personal-site transition is irreversible. Restore a database backup before rolling back this release.',
        );
    }
};
