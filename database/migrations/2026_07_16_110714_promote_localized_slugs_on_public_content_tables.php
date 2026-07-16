<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('articles')
            ->select(['id', 'slugs'])
            ->orderBy('id')
            ->each(function (object $article): void {
                $slug = $this->decodeTranslations($article->slugs);

                $this->assertLocalizedSlug($slug, 'Article', $article->id);

                DB::table('articles')
                    ->where('id', $article->id)
                    ->update([
                        'slug' => json_encode($slug, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'slug_ar' => $slug['ar'],
                        'slug_en' => $slug['en'],
                    ]);
            });

        $this->backfillLocalizedSlugs('projects', 'title');
        $this->backfillLocalizedSlugs('services', 'name');

        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn('slugs');
        });

        foreach (['projects', 'services'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropUnique(['slug']);
                $table->dropColumn('slug');
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('localized_slug', 'slug');
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('key', 80)->nullable(false)->change();
                $table->json('slug')->nullable(false)->change();
                $table->string('slug_ar', 190)->nullable(false)->change();
                $table->string('slug_en', 190)->nullable(false)->change();
            });
        }

        Schema::table('articles', function (Blueprint $table): void {
            $table->json('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->json('slugs')->nullable()->after('key');
        });

        DB::table('articles')
            ->select(['id', 'slug'])
            ->orderBy('id')
            ->each(function (object $article): void {
                DB::table('articles')
                    ->where('id', $article->id)
                    ->update(['slugs' => $article->slug]);
            });

        Schema::table('articles', function (Blueprint $table): void {
            $table->json('slugs')->nullable(false)->change();
            $table->dropColumn('slug');
        });

        foreach (['projects', 'services'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('legacy_slug', 80)->nullable()->after('key');
            });

            DB::table($tableName)
                ->select(['id', 'key'])
                ->orderBy('id')
                ->each(function (object $record) use ($tableName): void {
                    DB::table($tableName)
                        ->where('id', $record->id)
                        ->update(['legacy_slug' => $record->key]);
                });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('slug');
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->renameColumn('legacy_slug', 'slug');
            });

            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('slug', 80)->nullable(false)->change();
                $table->unique('slug');
            });
        }
    }

    private function backfillLocalizedSlugs(string $table, string $sourceColumn): void
    {
        $usedArabicSlugs = [];
        $usedEnglishSlugs = [];

        DB::table($table)
            ->select(['id', 'slug', $sourceColumn])
            ->orderBy('id')
            ->each(function (object $record) use ($table, $sourceColumn, &$usedArabicSlugs, &$usedEnglishSlugs): void {
                $translations = $this->decodeTranslations($record->{$sourceColumn});
                $key = trim((string) $record->slug);

                if ($key === '') {
                    throw new RuntimeException(ucfirst($table)." [{$record->id}] is missing its stable key.");
                }

                $arabicSource = trim((string) ($translations['ar'] ?? ''));
                $arabicBaseSlug = $this->slug($arabicSource, 'ar');
                $arabicSlug = $this->uniqueSlug($arabicBaseSlug !== '' ? $arabicBaseSlug : $key, $usedArabicSlugs);
                $englishSlug = $this->uniqueSlug($this->slug($key, 'en'), $usedEnglishSlugs);
                $slug = ['ar' => $arabicSlug, 'en' => $englishSlug];

                $this->assertLocalizedSlug($slug, ucfirst(Str::singular($table)), $record->id);

                DB::table($table)
                    ->where('id', $record->id)
                    ->update([
                        'key' => $key,
                        'localized_slug' => json_encode($slug, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                        'slug_ar' => $arabicSlug,
                        'slug_en' => $englishSlug,
                    ]);
            });
    }

    /** @return array<string, mixed> */
    private function decodeTranslations(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true, flags: JSON_THROW_ON_ERROR);

        return is_array($decoded) ? $decoded : [];
    }

    private function slug(string $value, string $locale): string
    {
        if ($locale !== 'ar') {
            return Str::slug($value);
        }

        $slug = preg_replace('/[^\p{Arabic}\p{N}\s-]+/u', '', trim($value)) ?? '';
        $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? '';
        $slug = preg_replace('/-+/u', '-', $slug) ?? '';

        return trim($slug, '-');
    }

    /** @param array<string, true> $usedSlugs */
    private function uniqueSlug(string $slug, array &$usedSlugs): string
    {
        $slug = mb_substr($slug, 0, 180);
        $candidate = $slug;
        $suffix = 1;

        while (isset($usedSlugs[$candidate])) {
            $suffixValue = '-'.$suffix++;
            $candidate = mb_substr($slug, 0, 180 - mb_strlen($suffixValue)).$suffixValue;
        }

        $usedSlugs[$candidate] = true;

        return $candidate;
    }

    /** @param array<string, mixed> $slug */
    private function assertLocalizedSlug(array $slug, string $model, int $id): void
    {
        if (blank($slug['ar'] ?? null) || blank($slug['en'] ?? null)) {
            throw new RuntimeException("{$model} [{$id}] is missing a localized slug.");
        }
    }
};
