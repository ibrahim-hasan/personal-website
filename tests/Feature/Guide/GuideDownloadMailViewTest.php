<?php

namespace Tests\Feature\Guide;

use App\Mail\GuideDownloadMail;
use App\Models\GuideDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuideDownloadMailViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_download_mail_renders_view(): void
    {
        $downloader = GuideDownloader::factory()->create([
            'email' => 'guide@example.com',
        ]);

        $token = $downloader->generateDownloadToken();

        $html = (new GuideDownloadMail($downloader, $token))->render();

        $this->assertStringContainsString(__('mail.guide_download_subject'), $html);
        $this->assertStringContainsString(route('guide.download', ['token' => $token]), $html);
    }
}
