<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\IntellectualLibrary;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $this->addIntellectualLibraryImages();
        $this->addAuthorAvatars();
    }

    private function addIntellectualLibraryImages(): void
    {
        /** @var Collection<int, IntellectualLibrary> $items */
        $items = IntellectualLibrary::query()->get();

        foreach ($items as $item) {
            /** @var IntellectualLibrary $item */
            $this->ensureImageCollection($item, 'featured_image', 1200, 700, 'featured');
            $this->ensureImageCollection($item, 'cover_image', 1200, 630, 'cover');
            $this->ensureImageCollection($item, 'og_image', 1200, 630, 'og');
        }

        $this->command?->info('Intellectual library images seeded.');
    }

    private function ensureImageCollection(
        IntellectualLibrary $item,
        string $collection,
        int $width,
        int $height,
        string $suffix
    ): void {
        if ($item->hasMedia($collection)) {
            return;
        }

        $seed = "library-{$item->id}-{$suffix}";
        $fileName = "library-{$item->id}-{$suffix}.jpg";

        try {
            $item->addMediaFromUrl("https://picsum.photos/seed/{$seed}/{$width}/{$height}")
                ->usingFileName($fileName)
                ->toMediaCollection($collection);

            return;
        } catch (\Throwable $exception) {
            $this->command?->warn("Could not fetch {$collection} for library #{$item->id}: {$exception->getMessage()}");
        }

        $fallback = public_path('images/placeholder.png');

        if (! is_file($fallback)) {
            return;
        }

        try {
            $item->addMedia($fallback)
                ->usingFileName($fileName)
                ->toMediaCollection($collection);
        } catch (\Throwable $exception) {
            $this->command?->warn("Could not attach fallback {$collection} for library #{$item->id}: {$exception->getMessage()}");
        }
    }

    private function addAuthorAvatars(): void
    {
        /** @var Collection<int, Author> $authors */
        $authors = Author::query()->get();

        foreach ($authors as $author) {
            /** @var Author $author */
            $this->ensureAuthorAvatar($author);
        }

        $this->command?->info('Author avatars seeded.');
    }

    private function ensureAuthorAvatar(Author $author): void
    {
        if ($author->hasMedia('avatar')) {
            return;
        }

        $seed = "author-{$author->id}-avatar";
        $fileName = "author-{$author->id}-avatar.jpg";

        try {
            $author->addMediaFromUrl("https://picsum.photos/seed/{$seed}/512/512")
                ->usingFileName($fileName)
                ->toMediaCollection('avatar');

            return;
        } catch (\Throwable $exception) {
            $this->command?->warn("Could not fetch avatar for author #{$author->id}: {$exception->getMessage()}");
        }

        $fallback = public_path('images/placeholder.png');

        if (! is_file($fallback)) {
            return;
        }

        try {
            $author->addMedia($fallback)
                ->usingFileName($fileName)
                ->toMediaCollection('avatar');
        } catch (\Throwable $exception) {
            $this->command?->warn("Could not attach fallback avatar for author #{$author->id}: {$exception->getMessage()}");
        }
    }
}
