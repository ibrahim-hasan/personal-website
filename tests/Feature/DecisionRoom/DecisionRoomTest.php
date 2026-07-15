<?php

namespace Tests\Feature\DecisionRoom;

use App\Livewire\Website\DecisionRoom;
use Livewire\Livewire;
use Tests\TestCase;

class DecisionRoomTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('en');
    }

    public function test_it_renders_the_optional_room_with_four_business_challenge_paths(): void
    {
        Livewire::test(DecisionRoom::class)
            ->assertSet('step', 1)
            ->assertSet('selectedChallenge', null)
            ->assertSet('primaryFriction', null)
            ->assertSet('desiredOutcome', null)
            ->assertSee('Optional decision room')
            ->assertSee('AI adoption')
            ->assertSee('Digital transformation')
            ->assertSee('Product or platform direction')
            ->assertSee('Operations and automation')
            ->assertSeeHtml('aria-live="polite"')
            ->assertDontSee('A useful starting point');
    }

    public function test_invalid_action_options_are_ignored_without_changing_state(): void
    {
        Livewire::test(DecisionRoom::class)
            ->call('selectChallenge', 'not-a-challenge')
            ->call('selectFriction', 'not-a-friction')
            ->call('selectOutcome', 'not-an-outcome')
            ->assertSet('step', 1)
            ->assertSet('selectedChallenge', null)
            ->assertSet('primaryFriction', null)
            ->assertSet('desiredOutcome', null)
            ->call('selectChallenge', 'ai-adoption')
            ->call('selectFriction', 'fragmented-journey')
            ->call('selectOutcome', 'phased-roadmap')
            ->assertSet('step', 2)
            ->assertSet('selectedChallenge', 'ai-adoption')
            ->assertSet('primaryFriction', null)
            ->assertSet('desiredOutcome', null)
            ->call('showRecommendation')
            ->assertSet('step', 2);
    }

    public function test_selecting_a_path_reveals_only_its_adaptive_follow_up_choices(): void
    {
        Livewire::test(DecisionRoom::class)
            ->call('selectChallenge', 'digital-transformation')
            ->assertSet('step', 2)
            ->assertSet('selectedChallenge', 'digital-transformation')
            ->assertSee('The customer or operating journey is split across disconnected channels')
            ->assertSee('A phased transformation roadmap')
            ->assertDontSee('Pilot outputs are not yet trusted in daily work')
            ->call('selectFriction', 'competing-priorities')
            ->call('selectOutcome', 'priority-map')
            ->assertSet('primaryFriction', 'competing-priorities')
            ->assertSet('desiredOutcome', 'priority-map')
            ->assertSet('step', 2);
    }

    public function test_complete_choices_generate_a_grounded_summary_and_consultation_url(): void
    {
        $expectedUrl = localized_route('contact', [
            'challenge' => 'product-platform',
            'friction' => 'unclear-product-direction',
            'outcome' => 'product-direction',
        ]).'#consultation';

        $component = Livewire::test(DecisionRoom::class)
            ->call('selectChallenge', 'product-platform')
            ->call('selectFriction', 'unclear-product-direction')
            ->call('selectOutcome', 'product-direction')
            ->call('showRecommendation')
            ->assertSet('step', 3)
            ->assertSee('A useful starting point')
            ->assertSee('Reconnect the roadmap to a clear decision')
            ->assertSee('The roadmap is not clearly tied to customer or business needs')
            ->assertSee('A sharper product direction')
            ->assertSee('This is a conversation starter based only on your selections.')
            ->assertSee($expectedUrl);

        $this->assertStringContainsString('challenge=product-platform', $expectedUrl);
        $this->assertStringContainsString('friction=unclear-product-direction', $expectedUrl);
        $this->assertStringContainsString('outcome=product-direction', $expectedUrl);
        $component->assertHasNoErrors();
    }

    public function test_recommendation_copy_is_localized_in_arabic(): void
    {
        app()->setLocale('ar');

        Livewire::test(DecisionRoom::class)
            ->call('selectChallenge', 'operations-automation')
            ->call('selectFriction', 'low-visibility')
            ->call('selectOutcome', 'visibility-plan')
            ->call('showRecommendation')
            ->assertSet('step', 3)
            ->assertSee('نقطة بداية مفيدة')
            ->assertSee('اجعل سير العمل مرئياً قبل أتمتته')
            ->assertSee('خطة لوضوح التشغيل')
            ->assertSee('وليست تشخيصاً آلياً أو وعداً بنتيجة محددة');
    }

    public function test_back_preserves_context_and_reset_clears_the_room(): void
    {
        Livewire::test(DecisionRoom::class)
            ->call('selectChallenge', 'ai-adoption')
            ->call('selectFriction', 'trust-and-review')
            ->call('selectOutcome', 'bounded-pilot')
            ->call('showRecommendation')
            ->assertSet('step', 3)
            ->call('back')
            ->assertSet('step', 2)
            ->assertSet('selectedChallenge', 'ai-adoption')
            ->assertSet('primaryFriction', 'trust-and-review')
            ->assertSet('desiredOutcome', 'bounded-pilot')
            ->call('back')
            ->assertSet('step', 1)
            ->assertSet('selectedChallenge', 'ai-adoption')
            ->call('resetDecisionRoom')
            ->assertSet('step', 1)
            ->assertSet('selectedChallenge', null)
            ->assertSet('primaryFriction', null)
            ->assertSet('desiredOutcome', null);
    }
}
