<?php

namespace Tests\Feature\Seeders;

use App\Models\Article;
use App\Models\Service;
use Database\Seeders\ArticleSeeder;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\SeoAuthorityEditorialContentSeeder;
use Database\Seeders\SeoServiceRelationshipSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoServiceRelationshipSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_it_seeds_ordered_service_and_article_context_relationships_without_duplicates(): void
    {
        $this->seed([
            ServiceSeeder::class,
            ProjectSeeder::class,
            ArticleSeeder::class,
            SeoAuthorityEditorialContentSeeder::class,
            SeoServiceRelationshipSeeder::class,
            SeoServiceRelationshipSeeder::class,
        ]);

        $expected = [
            'transformation' => [
                'articles' => [
                    'digital-transformation-roadmap-process-to-impact',
                    'transformation-before-software',
                    'measure-digital-impact',
                ],
                'projects' => ['wafaa', 'bosalty'],
            ],
            'ai-adoption' => [
                'articles' => [
                    'ai-use-case-register-governance-template',
                    'ai-adoption-roadmap-saudi-companies-2026',
                    'first-ai-use-case',
                    'ai-value',
                ],
                'projects' => ['digi-pedia', 'maazim'],
            ],
            'data-governance' => [
                'articles' => ['data-governance-before-ai', 'data-readiness', 'ai-governance'],
                'projects' => ['rafid-360', 'digi-pedia'],
            ],
            'systems' => [
                'articles' => [
                    'workflow-audit-what-should-be-automated',
                    'automation-assistant-agent',
                    'ai-not-answer',
                ],
                'projects' => ['2060-investments', 'wafaa'],
            ],
        ];

        foreach ($expected as $serviceKey => $relationships) {
            $service = Service::query()->where('key', $serviceKey)->firstOrFail();

            $this->assertSame(
                $relationships['articles'],
                $service->articles()->pluck('articles.key')->all(),
            );
            $this->assertSame(
                $relationships['projects'],
                $service->projects()->pluck('projects.key')->all(),
            );
        }

        $expectedArticleProjects = [
            'transformation-before-software' => ['wafaa', 'bosalty'],
            'measure-digital-impact' => ['wafaa'],
            'first-ai-use-case' => ['digi-pedia', 'maazim'],
            'ai-value' => ['digi-pedia'],
            'data-readiness' => ['rafid-360', 'digi-pedia'],
            'ai-governance' => ['rafid-360'],
            'automation-assistant-agent' => ['2060-investments', 'wafaa'],
            'ai-not-answer' => ['2060-investments'],
            'ai-adoption-roadmap-saudi-companies-2026' => ['digi-pedia'],
            'ai-use-case-register-governance-template' => ['digi-pedia'],
            'data-governance-before-ai' => ['rafid-360'],
            'digital-transformation-roadmap-process-to-impact' => ['wafaa', 'bosalty'],
            'workflow-audit-what-should-be-automated' => ['2060-investments', 'wafaa'],
        ];

        foreach ($expectedArticleProjects as $articleKey => $projectKeys) {
            $article = Article::query()->where('key', $articleKey)->firstOrFail();

            $this->assertSame($projectKeys, $article->projects()->pluck('projects.key')->all());
        }
    }

    public function test_rerunning_seo_seeders_preserves_editorial_copy_semantically_and_relationship_order(): void
    {
        $this->seed([
            ServiceSeeder::class,
            ProjectSeeder::class,
            ArticleSeeder::class,
            SeoAuthorityEditorialContentSeeder::class,
            SeoServiceRelationshipSeeder::class,
        ]);

        $article = Article::query()
            ->where('key', 'ai-use-case-register-governance-template')
            ->firstOrFail();
        $service = Service::query()->where('key', 'ai-adoption')->firstOrFail();
        $editorBody = [
            'type' => 'doc',
            'content' => [
                [
                    'type' => 'paragraph',
                    'content' => [
                        ['type' => 'text', 'text' => 'Editor-controlled release copy.'],
                    ],
                ],
            ],
        ];

        $article->setTranslation('title', 'en', 'Editor-controlled governance register');
        $article->setTranslation('body', 'en', $editorBody);
        $article->forceFill([
            'editorial_revision' => 2,
            'is_published' => false,
        ])->save();

        DB::table('article_service')
            ->where('article_id', $article->getKey())
            ->where('service_id', $service->getKey())
            ->update(['sort_order' => 91]);

        $this->seed([
            SeoAuthorityEditorialContentSeeder::class,
            SeoServiceRelationshipSeeder::class,
        ]);

        $article->refresh();

        $this->assertSame('Editor-controlled governance register', $article->getTranslation('title', 'en', false));
        $this->assertEquals($editorBody, $article->getTranslation('body', 'en', false));
        $this->assertSame(2, $article->editorial_revision);
        $this->assertFalse($article->is_published);
        $this->assertSame(
            91,
            DB::table('article_service')
                ->where('article_id', $article->getKey())
                ->where('service_id', $service->getKey())
                ->value('sort_order'),
        );
    }
}
