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
            ->assertSee('data-uses-livewire="true"', false)
            ->assertSee('wire:navigate.preserve-scroll', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('<link rel="canonical" href="'.url('/en/work').'">', false)
            ->assertSee('<meta name="robots" content="noindex, follow, noarchive">', false);

        $workView = file_get_contents(resource_path('views/website/work.blade.php'));

        $this->assertStringNotContainsString('x-show=', $workView);
        $this->assertStringNotContainsString('projectFilter(', $workView);

        $javascript = file_get_contents(resource_path('js/app.js'));
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('const hasWireNavigateDirective = (link)', $javascript);
        $this->assertStringContainsString("name.startsWith('wire:navigate.')", $javascript);
        $this->assertStringContainsString('const markWorkFilterNavigation = (event)', $javascript);
        $this->assertStringContainsString('const isWorkFilterNavigation = destination instanceof URL', $javascript);
        $this->assertStringContainsString("document.addEventListener('livewire:navigate', markWorkFilterNavigation);", $javascript);
        $this->assertStringContainsString('const preserveWorkFilterNavigationState = (event)', $javascript);
        $this->assertStringContainsString("document.addEventListener('livewire:navigating', preserveWorkFilterNavigationState);", $javascript);
        $this->assertStringContainsString('skipWorkFilterEntranceMotion', $javascript);
        $this->assertStringContainsString("element.closest('.work-archive')", $javascript);
        $this->assertStringContainsString('const queueControlUpdate = () => {', $javascript);
        $this->assertStringContainsString('let updateFrame = null;', $javascript);
        $this->assertStringContainsString('const initializeControls = () => {', $javascript);
        $this->assertStringContainsString('const updateAfterFontsLoad = () => {', $javascript);
        $this->assertStringContainsString('resizeObserver?.observe(container);', $javascript);
        $this->assertStringContainsString('const fontReady = document.fonts?.ready;', $javascript);
        $this->assertStringContainsString("window.addEventListener('load', initializeControls", $javascript);
        $this->assertStringNotContainsString("toggleAttribute('data-overflow-active'", $javascript);
        $this->assertStringContainsString('.filter-bar :is(a, button)', $css);
        $this->assertStringContainsString('.filter-bar-shell > .filter-bar', $css);
        $this->assertStringContainsString('grid-column: 2;', $css);
        $this->assertStringContainsString("html[data-work-filter-navigation='true'].motion-capable .work-archive [data-reveal]", $css);
    }

    public function test_invalid_lens_returns_a_404_and_an_empty_lens_redirects_to_the_canonical_work_page(): void
    {
        $this->get('/work?lens=not-a-real-lens')->assertNotFound();
        $this->get('/work?lens=')->assertOk();
    }
}
