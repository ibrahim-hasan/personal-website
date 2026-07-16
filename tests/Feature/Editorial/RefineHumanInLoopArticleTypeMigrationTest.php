<?php

namespace Tests\Feature\Editorial;

use App\Models\Article;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RefineHumanInLoopArticleTypeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refines_known_defaults_idempotently_and_reverses_them(): void
    {
        $article = Article::factory()->create([
            'key' => 'human-in-loop',
            'type' => ['ar' => 'مقال تصميمي', 'en' => 'Workflow design'],
        ]);
        $unrelated = Article::factory()->create([
            'type' => ['ar' => 'نوع مخصص', 'en' => 'Custom type'],
        ]);
        $migration = $this->migration();

        $migration->up();
        $migration->up();

        $this->assertSame(
            ['ar' => 'دليل تصميم سير العمل', 'en' => 'Workflow design guide'],
            $article->fresh()->getTranslations('type'),
        );
        $this->assertSame(
            ['ar' => 'نوع مخصص', 'en' => 'Custom type'],
            $unrelated->fresh()->getTranslations('type'),
        );

        $migration->down();

        $this->assertSame(
            ['ar' => 'مقال تصميمي', 'en' => 'Workflow design'],
            $article->fresh()->getTranslations('type'),
        );
    }

    public function test_it_preserves_editorial_overrides_per_locale(): void
    {
        $article = Article::factory()->create([
            'key' => 'human-in-loop',
            'type' => ['ar' => 'تصنيف حرره المحرر', 'en' => 'Workflow design'],
        ]);

        $this->migration()->up();

        $this->assertSame(
            ['ar' => 'تصنيف حرره المحرر', 'en' => 'Workflow design guide'],
            $article->fresh()->getTranslations('type'),
        );
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_07_16_055836_refine_human_in_loop_article_type.php');
    }
}
