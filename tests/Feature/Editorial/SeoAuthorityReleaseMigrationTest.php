<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use Database\Seeders\ArticleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class SeoAuthorityReleaseMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_historical_release_uses_an_immutable_transformed_payload(): void
    {
        $records = require database_path('migrations/data/seo_authority_editorial_content_v1.php');
        $migrationSource = file_get_contents(
            database_path('migrations/2026_08_28_211715_publish_seo_authority_editorial_content.php'),
        );

        $this->assertIsArray($records);
        $this->assertSame([
            'ai-governance',
            'first-ai-use-case',
            'data-readiness',
            'ai-adoption-roadmap-saudi-companies-2026',
            'ai-use-case-register-governance-template',
            'data-governance-before-ai',
            'digital-transformation-roadmap-process-to-impact',
            'workflow-audit-what-should-be-automated',
        ], array_column($records, 'key'));
        $this->assertSame(
            '3ba8030546c4f830f47bc7513a63cf7fedcf3b31caf6c6b3a3423bda974db4c5',
            hash('sha256', json_encode(
                $records,
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES,
            )),
        );
        $this->assertIsString($migrationSource);
        $this->assertStringNotContainsString('SeoAuthorityArticlePayload', $migrationSource);
        $this->assertStringNotContainsString('ArticleBody', $migrationSource);
    }

    public function test_an_update_record_cannot_be_silently_recreated_when_its_source_is_missing(): void
    {
        $this->seed(ArticleSeeder::class);

        Article::query()->where('key', 'ai-governance')->firstOrFail()->forceDelete();

        $migration = require database_path(
            'migrations/2026_08_28_211715_publish_seo_authority_editorial_content.php',
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Required source article [ai-governance] is missing');

        try {
            $migration->up();
        } finally {
            $this->assertDatabaseMissing('articles', ['key' => 'ai-governance']);
        }
    }
}
