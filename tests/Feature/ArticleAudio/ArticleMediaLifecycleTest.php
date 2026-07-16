<?php

namespace Tests\Feature\ArticleAudio;

use App\Models\Article;
use App\Models\ArticleAudio;
use App\Models\ArticleNarration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleMediaLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_creating_an_article_links_existing_audio_and_narration_records(): void
    {
        $audio = ArticleAudio::factory()->create([
            'article_key' => 'legacy-audio-key',
            'locale' => 'ar',
        ]);
        $narration = ArticleNarration::factory()->create([
            'article_key' => 'legacy-audio-key',
            'locale' => 'ar',
        ]);

        $this->assertNull($audio->article_id);
        $this->assertNull($narration->article_id);

        $article = Article::factory()->create(['key' => 'legacy-audio-key']);

        $this->assertTrue($audio->fresh()->article->is($article));
        $this->assertTrue($narration->fresh()->article->is($article));
        $this->assertTrue($article->audioTracks()->firstOrFail()->is($audio));
        $this->assertTrue($article->narrations()->firstOrFail()->is($narration));

        $article->update(['key' => 'renamed-audio-key']);

        $this->assertSame('renamed-audio-key', $audio->fresh()->article_key);
        $this->assertSame('renamed-audio-key', $narration->fresh()->article_key);
    }

    public function test_the_backfill_links_media_when_articles_already_exist(): void
    {
        $article = Article::factory()->create();
        $audio = ArticleAudio::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
        ]);
        $narration = ArticleNarration::factory()->create([
            'article_key' => $article->key,
            'locale' => 'ar',
        ]);

        DB::table('article_audio')->where('id', $audio->getKey())->update(['article_id' => null]);
        DB::table('article_narrations')->where('id', $narration->getKey())->update(['article_id' => null]);

        $migration = require database_path('migrations/2026_07_15_234440_backfill_article_media_article_ids.php');
        $migration->up();

        $this->assertSame($article->getKey(), $audio->fresh()->article_id);
        $this->assertSame($article->getKey(), $narration->fresh()->article_id);
    }

    public function test_soft_deletion_preserves_media_but_force_deletion_removes_records_and_files(): void
    {
        $article = Article::factory()->create();
        $audioPath = 'article-audio/en/permanent-track.mp3';
        $samplePath = 'article-audio/samples/en/permanent-sample.mp3';
        Storage::disk('public')->put($audioPath, 'audio');
        Storage::disk('public')->put($samplePath, 'sample');

        $audio = ArticleAudio::factory()->create([
            'article_key' => $article->key,
            'locale' => 'en',
            'path' => $audioPath,
        ]);
        $narration = ArticleNarration::factory()->create([
            'article_key' => $article->key,
            'locale' => 'en',
            'samples' => [
                'eleven_v3' => [
                    'status' => 'ready',
                    'disk' => 'public',
                    'path' => $samplePath,
                ],
            ],
        ]);

        $this->assertSame($article->getKey(), $audio->article_id);
        $this->assertSame($article->getKey(), $narration->article_id);

        $article->delete();

        $this->assertSoftDeleted($article);
        $this->assertModelExists($audio);
        $this->assertModelExists($narration);
        Storage::disk('public')->assertExists($audioPath);
        Storage::disk('public')->assertExists($samplePath);

        $audio->forceFill(['last_error' => 'Retained while the article is archived.'])->save();
        $narration->forceFill(['preparation_notes' => ['Retained while archived.']])->save();
        $this->assertSame($article->getKey(), $audio->fresh()->article_id);
        $this->assertSame($article->getKey(), $narration->fresh()->article_id);

        $article->forceDelete();

        $this->assertModelMissing($audio);
        $this->assertModelMissing($narration);
        Storage::disk('public')->assertMissing($audioPath);
        Storage::disk('public')->assertMissing($samplePath);
    }
}
