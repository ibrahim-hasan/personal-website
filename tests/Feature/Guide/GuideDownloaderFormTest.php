<?php

namespace Tests\Feature\Guide;

use App\Livewire\Website\GuideDownloaderForm;
use App\Mail\GuideDownloadMail;
use App\Models\Guide;
use App\Models\GuideDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class GuideDownloaderFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_it_creates_downloader_and_sends_guide_email(): void
    {
        Mail::fake();

        $guide = Guide::factory()->create();

        Livewire::test(GuideDownloaderForm::class, ['guideId' => $guide->id])
            ->set('form.email', 'lead@example.com')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('form.email', '');

        $downloader = GuideDownloader::query()
            ->where('email', 'lead@example.com')
            ->where('guide_id', $guide->id)
            ->first();

        $this->assertNotNull($downloader);
        $this->assertTrue($downloader->is_mail_sent);
        $this->assertNotNull($downloader->download_token);

        Mail::assertSent(GuideDownloadMail::class, function (GuideDownloadMail $mail) use ($downloader): bool {
            return $mail->downloader->is($downloader);
        });
    }

    public function test_it_sends_for_existing_downloader_when_mail_not_sent(): void
    {
        Mail::fake();

        $guide = Guide::factory()->create();

        $downloader = GuideDownloader::factory()->create([
            'guide_id' => $guide->id,
            'email' => 'existing@example.com',
            'is_mail_sent' => false,
        ]);

        Livewire::test(GuideDownloaderForm::class, ['guideId' => $guide->id])
            ->set('form.email', 'existing@example.com')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('form.email', '');

        $this->assertTrue($downloader->fresh()->is_mail_sent);

        Mail::assertSent(GuideDownloadMail::class, function (GuideDownloadMail $mail) use ($downloader): bool {
            return $mail->downloader->is($downloader);
        });
    }

    public function test_it_resends_fresh_link_for_existing_downloader_after_cooldown_window(): void
    {
        Mail::fake();

        $guide = Guide::factory()->create();

        $downloader = GuideDownloader::factory()->create([
            'guide_id' => $guide->id,
            'email' => 'existing@example.com',
            'is_mail_sent' => true,
            'download_token' => 'old-token',
            'token_expires_at' => now()->addHour(),
        ]);

        Livewire::test(GuideDownloaderForm::class, ['guideId' => $guide->id])
            ->set('form.email', 'existing@example.com')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('errorMessage', '');

        $this->assertNotEquals('old-token', $downloader->fresh()->download_token);

        Mail::assertSent(GuideDownloadMail::class, function (GuideDownloadMail $mail) use ($downloader): bool {
            return $mail->downloader->is($downloader);
        });
    }

    public function test_it_blocks_resend_during_two_minute_cooldown_window(): void
    {
        Mail::fake();

        $guide = Guide::factory()->create();

        $downloader = GuideDownloader::factory()->create([
            'guide_id' => $guide->id,
            'email' => 'existing@example.com',
            'is_mail_sent' => true,
        ]);

        Cache::put('guide-download:resend-cooldown:'.$guide->id.':existing@example.com', true, now()->addMinutes(2));

        Livewire::test(GuideDownloaderForm::class, ['guideId' => $guide->id])
            ->set('form.email', 'existing@example.com')
            ->call('submit')
            ->assertSet('submitted', false)
            ->assertSet('errorMessage', __('guide.resend_wait_message'));

        Mail::assertNothingSent();
        $this->assertTrue($downloader->fresh()->is_mail_sent);
    }
}
