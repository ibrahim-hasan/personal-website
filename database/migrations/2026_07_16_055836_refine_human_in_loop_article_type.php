<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array OLD_TYPES = [
        'ar' => 'مقال تصميمي',
        'en' => 'Workflow design',
    ];

    private const array NEW_TYPES = [
        'ar' => 'دليل تصميم سير العمل',
        'en' => 'Workflow design guide',
    ];

    public function up(): void
    {
        $this->replaceKnownDefaults(self::OLD_TYPES, self::NEW_TYPES);
    }

    public function down(): void
    {
        $this->replaceKnownDefaults(self::NEW_TYPES, self::OLD_TYPES);
    }

    /**
     * @param  array{ar: string, en: string}  $expected
     * @param  array{ar: string, en: string}  $replacement
     */
    private function replaceKnownDefaults(array $expected, array $replacement): void
    {
        if (! Schema::hasTable('articles') || ! Schema::hasColumn('articles', 'type')) {
            return;
        }

        $article = DB::table('articles')
            ->select(['id', 'type'])
            ->where('key', 'human-in-loop')
            ->first();

        if ($article === null || ! is_string($article->type)) {
            return;
        }

        $types = json_decode($article->type, true, flags: JSON_THROW_ON_ERROR);
        $changed = false;

        foreach ($replacement as $locale => $label) {
            if (($types[$locale] ?? null) !== $expected[$locale]) {
                continue;
            }

            $types[$locale] = $label;
            $changed = true;
        }

        if (! $changed) {
            return;
        }

        DB::table('articles')
            ->where('id', $article->id)
            ->update([
                'type' => json_encode($types, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'updated_at' => now(),
            ]);
    }
};
