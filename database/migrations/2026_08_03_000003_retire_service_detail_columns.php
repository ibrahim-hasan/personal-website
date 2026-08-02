<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('services')) {
            return;
        }

        $hasArabicSlug = Schema::hasColumn('services', 'slug_ar');
        $hasEnglishSlug = Schema::hasColumn('services', 'slug_en');

        if ($hasArabicSlug || $hasEnglishSlug) {
            Schema::table('services', function (Blueprint $table) use ($hasArabicSlug, $hasEnglishSlug): void {
                if ($hasArabicSlug) {
                    $table->dropUnique(['slug_ar']);
                }

                if ($hasEnglishSlug) {
                    $table->dropUnique(['slug_en']);
                }
            });
        }

        $columns = array_values(array_filter([
            Schema::hasColumn('services', 'slug') ? 'slug' : null,
            $hasArabicSlug ? 'slug_ar' : null,
            $hasEnglishSlug ? 'slug_en' : null,
            Schema::hasColumn('services', 'seo_title') ? 'seo_title' : null,
            Schema::hasColumn('services', 'seo_description') ? 'seo_description' : null,
        ]));

        if ($columns !== []) {
            Schema::table('services', function (Blueprint $table) use ($columns): void {
                $table->dropColumn($columns);
            });
        }
    }

    public function down(): void
    {
        throw new RuntimeException('Service detail fields were removed. Restore a database backup to recover them.');
    }
};
