<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('articles')
            ->select(['id', 'key'])
            ->orderBy('id')
            ->chunkById(100, function ($articles): void {
                foreach ($articles as $article) {
                    DB::table('article_audio')
                        ->whereNull('article_id')
                        ->where('article_key', $article->key)
                        ->update(['article_id' => $article->id]);

                    DB::table('article_narrations')
                        ->whereNull('article_id')
                        ->where('article_key', $article->key)
                        ->update(['article_id' => $article->id]);
                }
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('article_audio')->update(['article_id' => null]);
        DB::table('article_narrations')->update(['article_id' => null]);
    }
};
