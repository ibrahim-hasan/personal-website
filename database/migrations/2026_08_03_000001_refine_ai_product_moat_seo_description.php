<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const string PREVIOUS_DESCRIPTION = 'A practical guide to durable AI product advantage through proprietary context, compounding data, workflow integration, trust, distribution, and fast learning.';

    private const string REFINED_DESCRIPTION = 'A practical guide to durable AI product advantage through proprietary context, compounding data, workflow integration, trust, and fast learning.';

    public function up(): void
    {
        $this->replaceKnownDescription(self::PREVIOUS_DESCRIPTION, self::REFINED_DESCRIPTION);
    }

    public function down(): void
    {
        $this->replaceKnownDescription(self::REFINED_DESCRIPTION, self::PREVIOUS_DESCRIPTION);
    }

    private function replaceKnownDescription(string $expected, string $replacement): void
    {
        if (! Schema::hasTable('articles') || ! Schema::hasColumn('articles', 'seo_description')) {
            return;
        }

        $columns = ['id', 'seo_description'];

        if (Schema::hasColumn('articles', 'editorial_revision')) {
            $columns[] = 'editorial_revision';
        }

        $article = DB::table('articles')
            ->select($columns)
            ->where('key', 'ai-product-moat')
            ->first();

        if ($article === null || ! is_string($article->seo_description)) {
            return;
        }

        try {
            $descriptions = json_decode($article->seo_description, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return;
        }

        if (! is_array($descriptions) || ($descriptions['en'] ?? null) !== $expected) {
            return;
        }

        $descriptions['en'] = $replacement;
        $updates = [
            'seo_description' => json_encode(
                $descriptions,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            ),
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('articles', 'editorial_revision')) {
            $updates['editorial_revision'] = (int) ($article->editorial_revision ?? 0) + 1;
        }

        DB::table('articles')->where('id', $article->id)->update($updates);
    }
};
