<?php

namespace App\Console\Commands;

use App\Models\Article;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('articles:backfill-media')]
#[Description('Import legacy article image paths into their Media Library collection')]
class BackfillArticleMedia extends Command
{
    public function handle(): int
    {
        $imported = 0;
        $skipped = 0;

        Article::query()->eachById(function (Article $article) use (&$imported, &$skipped): void {
            if ($article->hasMedia(Article::IMAGE_COLLECTION) || blank($article->image)) {
                return;
            }

            $publicDirectory = realpath(public_path());
            $sourcePath = realpath(public_path($article->image));

            if ($publicDirectory === false || $sourcePath === false || ! str_starts_with($sourcePath, $publicDirectory.DIRECTORY_SEPARATOR)) {
                $skipped++;

                return;
            }

            $article
                ->addMedia($sourcePath)
                ->preservingOriginal()
                ->toMediaCollection(Article::IMAGE_COLLECTION);

            $imported++;
        });

        $this->components->info("Imported {$imported} article images; skipped {$skipped} missing or unsafe paths.");

        return self::SUCCESS;
    }
}
