<?php

use App\Models\Article;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var array<string, array{image: string, hash: string}> */
    private const array RELEASES = [
        'ai-adoption-roadmap-saudi-companies-2026' => [
            'image' => 'images/ibrahim/workflow-map.png',
            'hash' => '9d261eed9164c7c187d102c9ac3a5207687a652eee63886dd9999ef6241e27e3',
        ],
        'ai-use-case-register-governance-template' => [
            'image' => 'images/ibrahim/product-systems.png',
            'hash' => 'b1213b871d6dc289851b7331bf73220fd30a7b603200c5eb0805afae12be1932',
        ],
        'data-governance-before-ai' => [
            'image' => 'images/ibrahim/rag-console.png',
            'hash' => '15aece9a08fc1c0f15e8d4522be5350129c88a353072a461d4ebb7bdeccf8f45',
        ],
        'digital-transformation-roadmap-process-to-impact' => [
            'image' => 'images/ibrahim/hero-workspace.png',
            'hash' => 'c229f01003b24842e5e3f701f862d9c5b956effcb1282eb002a167865a09f481',
        ],
        'workflow-audit-what-should-be-automated' => [
            'image' => 'images/ibrahim/automation-board.png',
            'hash' => 'b4a5f77080b916ff8a0574afad47b8c8058f1cb4fd997c2a596184891eda72b3',
        ],
    ];

    public function up(): void
    {
        if (! Schema::hasTable('articles')
            || ! Schema::hasTable('media')
            || ! Schema::hasColumns('articles', [
                'key',
                'image',
                'modified_at',
                'editorial_revision',
                'is_published',
            ])) {
            return;
        }

        foreach (self::RELEASES as $articleKey => $release) {
            $article = Article::query()->where('key', $articleKey)->first();

            if (! $article instanceof Article || ! $this->isUntouchedReleaseBaseline($article, $release['image'])) {
                continue;
            }

            $this->ensureManagedHero($article, $release);

            if (! $article->is_published) {
                $article->update(['is_published' => true]);
            }
        }
    }

    public function down(): void
    {
        throw new LogicException(
            'The managed-media SEO resource release is intentionally irreversible. Use a forward migration for corrections.',
        );
    }

    private function isUntouchedReleaseBaseline(Article $article, string $expectedImage): bool
    {
        return $article->editorial_revision === 1
            && $article->modified_at?->toDateString() === '2026-08-29'
            && $article->image === $expectedImage;
    }

    /** @param array{image: string, hash: string} $release */
    private function ensureManagedHero(Article $article, array $release): void
    {
        if ($article->hasMedia(Article::IMAGE_COLLECTION)) {
            return;
        }

        $source = public_path($release['image']);
        $actualHash = is_file($source) ? hash_file('sha256', $source) : false;

        if (! is_string($actualHash) || ! hash_equals($release['hash'], $actualHash)) {
            throw new RuntimeException("Managed release image is unavailable or changed for [{$article->key}].");
        }

        try {
            $extension = pathinfo($source, PATHINFO_EXTENSION);
            $article
                ->addMedia($source)
                ->preservingOriginal()
                ->usingName($article->key)
                ->usingFileName($article->key.'.'.$extension)
                ->toMediaCollection(Article::IMAGE_COLLECTION);
        } catch (Throwable $exception) {
            throw new RuntimeException(
                "Managed release image import failed for [{$article->key}].",
                previous: $exception,
            );
        }

        if (! $article->refresh()->hasMedia(Article::IMAGE_COLLECTION)) {
            throw new RuntimeException("Managed release image import did not persist for [{$article->key}].");
        }
    }
};
