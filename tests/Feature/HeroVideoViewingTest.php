<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HeroVideoViewingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_guest_homepage_uses_session_scoped_video_state(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-viewed="false"', false)
            ->assertDontSee('data-viewed-url=', false);
    }

    public function test_an_authenticated_viewer_can_record_video_completion(): void
    {
        $reader = User::factory()->create();

        $this->actingAs($reader)
            ->post('/reader/hero-video/viewed')
            ->assertNoContent();

        $this->assertNotNull($reader->refresh()->hero_video_seen_at);

        $this->actingAs($reader)
            ->get('/')
            ->assertOk()
            ->assertSee('data-viewed="true"', false)
            ->assertSee('data-viewed-url="'.url('/reader/hero-video/viewed').'"', false);
    }

    public function test_recording_completion_is_idempotent(): void
    {
        $seenAt = now()->subDay()->startOfSecond();
        $reader = User::factory()->create([
            'hero_video_seen_at' => $seenAt,
        ]);

        $this->actingAs($reader)
            ->post('/reader/hero-video/viewed')
            ->assertNoContent();

        $this->assertTrue($reader->refresh()->hero_video_seen_at->equalTo($seenAt));
    }

    public function test_a_guest_cannot_record_account_level_completion(): void
    {
        $this->post('/reader/hero-video/viewed')
            ->assertRedirect('/reader/login');
    }
}
