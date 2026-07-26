<?php

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkFilteringTest extends TestCase
{
    use RefreshDatabase;

    public function test_work_lenses_are_server_rendered_links_with_a_canonical_noindex_filtered_response(): void
    {
        Project::factory()->create([
            'lens' => 'operations',
            'title' => ['ar' => 'مشروع العمليات', 'en' => 'Operations project'],
        ]);
        Project::factory()->create([
            'lens' => 'ai-adoption',
            'title' => ['ar' => 'مشروع الذكاء الاصطناعي', 'en' => 'AI project'],
        ]);

        $this->get('/en/work?lens=operations')
            ->assertOk()
            ->assertSee('Operations project', false)
            ->assertDontSee('AI project', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('<link rel="canonical" href="'.url('/en/work').'">', false)
            ->assertSee('<meta name="robots" content="noindex, follow, noarchive">', false);

        $workView = file_get_contents(resource_path('views/website/work.blade.php'));

        $this->assertStringNotContainsString('x-show=', $workView);
        $this->assertStringNotContainsString('projectFilter(', $workView);
    }

    public function test_invalid_lens_returns_a_404_and_an_empty_lens_redirects_to_the_canonical_work_page(): void
    {
        $this->get('/work?lens=not-a-real-lens')->assertNotFound();
        $this->get('/work?lens=')->assertOk();
    }
}
