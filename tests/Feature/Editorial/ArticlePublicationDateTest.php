<?php

namespace Tests\Feature\Editorial;

use App\Actions\Editorial\PublishEditorialArticle;
use App\Actions\Editorial\SetEditorialArticlePublication;
use App\Models\Article;
use App\Models\EditorialArticleRevisionSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArticlePublicationDateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_publish_action_preserves_the_original_publication_date_across_unpublish_and_republish(): void
    {
        $originalPublishedAt = today()->subYear();
        $article = $this->readyArticle([
            'is_published' => true,
            'published_at' => $originalPublishedAt,
            'modified_at' => today()->subMonth(),
            'editorial_revision' => 7,
        ]);

        $article = app(SetEditorialArticlePublication::class)->handle($article, false, 7);

        $this->assertFalse($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
        $this->assertTrue($article->modified_at->isSameDay(Article::publicationToday()));
        $this->assertSame(8, $article->editorial_revision);
        $this->assertSame('article.unpublished_from_public', $article->revisionSnapshots()->where('revision', 8)->sole()->action);

        $this->travelTo(now()->addDay());

        $article = app(PublishEditorialArticle::class)->handle($article, 8);

        $this->assertTrue($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
        $this->assertTrue($article->modified_at->isSameDay(Article::publicationToday()));
        $this->assertSame(9, $article->editorial_revision);
        $this->assertSame('article.published', $article->revisionSnapshots()->where('revision', 9)->sole()->action);
        $this->assertSame(2, $article->revisionSnapshots()->count());
    }

    public function test_first_publication_uses_the_release_date_in_storage_and_structured_data(): void
    {
        $draftDate = today()->subMonths(3);
        $article = $this->readyArticle([
            'is_published' => false,
            'published_at' => $draftDate,
            'editorial_revision' => 3,
            'seo_title' => ['ar' => 'عنوان البحث', 'en' => 'Search title'],
            'seo_description' => ['ar' => 'وصف البحث', 'en' => 'Search description'],
        ]);

        $this->travelTo(now()->addWeek());
        $releaseDate = Article::publicationToday();

        $article = app(PublishEditorialArticle::class)->handle($article, 3);

        $this->assertTrue($article->published_at->isSameDay($releaseDate));
        $this->assertFalse($article->published_at->isSameDay($draftDate));

        $url = localized_route('writing.show', [
            'article' => $article->getTranslation('slug', 'en', false),
        ], locale: 'en');

        $this->get($url)
            ->assertOk()
            ->assertSee('"datePublished":"'.$releaseDate->toDateString().'"', false);
    }

    public function test_publish_actions_use_the_saudi_publication_date_for_both_publication_and_modification(): void
    {
        $this->travelTo(Carbon::parse('2026-08-28 21:30:00', 'UTC'));
        $publicationDate = Article::publicationToday();
        $publishActionArticle = $this->readyArticle([
            'is_published' => false,
            'published_at' => $publicationDate->copy()->subMonth(),
            'modified_at' => $publicationDate->copy()->subMonth(),
            'editorial_revision' => 3,
        ]);
        $setPublicationArticle = $this->readyArticle([
            'is_published' => false,
            'published_at' => $publicationDate->copy()->subMonth(),
            'modified_at' => $publicationDate->copy()->subMonth(),
            'editorial_revision' => 4,
        ]);

        $published = app(PublishEditorialArticle::class)->handle($publishActionArticle, 3);
        $setPublished = app(SetEditorialArticlePublication::class)->handle($setPublicationArticle, true, 4);

        foreach ([$published, $setPublished] as $article) {
            $this->assertTrue($article->published_at->isSameDay($publicationDate));
            $this->assertTrue($article->modified_at->isSameDay($publicationDate));
            $this->assertTrue($article->modified_at->greaterThanOrEqualTo($article->published_at));
        }
    }

    public function test_set_publication_action_preserves_the_original_publication_date_across_unpublish_and_republish(): void
    {
        $originalPublishedAt = today()->subMonths(8);
        $article = Article::factory()->create([
            'is_published' => true,
            'published_at' => $originalPublishedAt,
            'modified_at' => today()->subWeeks(2),
            'editorial_revision' => 11,
        ]);
        $setPublication = app(SetEditorialArticlePublication::class);

        $article = $setPublication->handle($article, false, 11);

        $this->assertFalse($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
        $this->assertTrue($article->modified_at->isSameDay(Article::publicationToday()));
        $this->assertSame(12, $article->editorial_revision);
        $this->assertSame('article.unpublished_from_public', $article->revisionSnapshots()->where('revision', 12)->sole()->action);

        $this->travelTo(now()->addDay());

        $article = $setPublication->handle($article, true, 12);

        $this->assertTrue($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
        $this->assertTrue($article->modified_at->isSameDay(Article::publicationToday()));
        $this->assertSame(13, $article->editorial_revision);
        $this->assertSame('article.published', $article->revisionSnapshots()->where('revision', 13)->sole()->action);
        $this->assertSame(2, $article->revisionSnapshots()->count());
    }

    public function test_redundant_unpublish_is_rejected_without_changing_a_draft_or_creating_a_snapshot(): void
    {
        $draftDate = today()->subMonths(2);
        $article = Article::factory()->create([
            'is_published' => false,
            'published_at' => $draftDate,
            'modified_at' => today()->subWeek(),
            'editorial_revision' => 5,
        ]);

        try {
            app(SetEditorialArticlePublication::class)->handle($article, false, 5, 'ar');
            $this->fail('A redundant unpublish transition should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                [trans('editorial_admin.feedback.invalid_publication_transition', [], 'ar')],
                $exception->errors()['article'],
            );
        }

        $article->refresh();

        $this->assertFalse($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($draftDate));
        $this->assertTrue($article->modified_at->isSameDay(today()->subWeek()));
        $this->assertSame(5, $article->editorial_revision);
        $this->assertSame(0, $article->revisionSnapshots()->count());
    }

    public function test_an_unpublished_snapshot_alone_does_not_preserve_a_draft_date_on_first_publication(): void
    {
        $draftDate = today()->subMonths(4);
        $article = $this->readyArticle([
            'is_published' => false,
            'published_at' => $draftDate,
            'editorial_revision' => 3,
        ]);
        EditorialArticleRevisionSnapshot::query()->create([
            'article_id' => $article->getKey(),
            'revision' => 2,
            'action' => 'article.unpublished',
            'service_keys' => [],
            'project_keys' => [],
        ]);

        $this->travelTo(now()->addDays(10));
        $releaseDate = Article::publicationToday();

        $article = app(PublishEditorialArticle::class)->handle($article, 3);

        $this->assertTrue($article->published_at->isSameDay($releaseDate));
        $this->assertFalse($article->published_at->isSameDay($draftDate));
        $this->assertSame('article.published', $article->revisionSnapshots()->where('revision', 4)->sole()->action);
    }

    public function test_legacy_publication_proof_does_not_overwrite_an_existing_current_revision_snapshot(): void
    {
        $originalPublishedAt = today()->subMonths(6);
        $article = $this->readyArticle([
            'is_published' => true,
            'published_at' => $originalPublishedAt,
            'editorial_revision' => 6,
        ]);
        EditorialArticleRevisionSnapshot::query()->create([
            'article_id' => $article->getKey(),
            'revision' => 6,
            'action' => 'article.updated',
            'service_keys' => [],
            'project_keys' => [],
        ]);

        $article = app(SetEditorialArticlePublication::class)->handle($article, false, 6);

        $this->assertSame('article.updated', $article->revisionSnapshots()->where('revision', 6)->sole()->action);
        $this->assertSame('article.unpublished_from_public', $article->revisionSnapshots()->where('revision', 7)->sole()->action);
        $this->assertSame(2, $article->revisionSnapshots()->count());

        $this->travelTo(now()->addDay());
        $article = app(PublishEditorialArticle::class)->handle($article, 7);

        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
    }

    public function test_publish_action_rejects_an_already_published_article_without_changing_it(): void
    {
        $originalPublishedAt = today()->subYear();
        $article = $this->readyArticle([
            'is_published' => true,
            'published_at' => $originalPublishedAt,
            'modified_at' => today()->subMonth(),
            'editorial_revision' => 9,
        ]);

        try {
            app(PublishEditorialArticle::class)->handle($article, 9, 'en');
            $this->fail('Publishing an already published article should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                [trans('editorial_admin.feedback.invalid_publication_transition', [], 'en')],
                $exception->errors()['article'],
            );
        }

        $article->refresh();

        $this->assertTrue($article->is_published);
        $this->assertTrue($article->published_at->isSameDay($originalPublishedAt));
        $this->assertTrue($article->modified_at->isSameDay(today()->subMonth()));
        $this->assertSame(9, $article->editorial_revision);
        $this->assertSame(0, $article->revisionSnapshots()->count());
    }

    /** @param array<string, mixed> $attributes */
    private function readyArticle(array $attributes): Article
    {
        $article = Article::factory()->create($attributes);

        $article
            ->addMedia(UploadedFile::fake()->image('managed-hero.jpg', 1600, 900))
            ->toMediaCollection(Article::IMAGE_COLLECTION);

        return $article->refresh();
    }
}
