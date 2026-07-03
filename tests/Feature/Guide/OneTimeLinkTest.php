<?php

namespace Tests\Feature\Guide;

use App\Models\GuideDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneTimeLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_download_token_can_be_generated(): void
    {
        $downloader = GuideDownloader::factory()->create();

        $token = $downloader->generateDownloadToken();

        $this->assertNotNull($token);
        $this->assertEquals(32, strlen($token));
    }

    public function test_guide_token_is_valid_immediately(): void
    {
        $downloader = GuideDownloader::factory()->create();

        $token = $downloader->generateDownloadToken();

        $this->assertTrue($downloader->isTokenValid($token));
    }

    public function test_used_token_is_invalid(): void
    {
        $downloader = GuideDownloader::factory()->create();

        $token = $downloader->generateDownloadToken();

        $downloader->markTokenAsUsed($token);

        $this->assertFalse($downloader->isTokenValid($token));
    }

    public function test_invalid_token_is_rejected(): void
    {
        $downloader = GuideDownloader::factory()->create();

        $this->assertFalse($downloader->isTokenValid('invalid-token'));
    }

    public function test_token_expires_after_set_time(): void
    {
        $downloader = GuideDownloader::factory()->create([
            'token_expires_at' => now()->subHour(),
        ]);

        $this->assertFalse($downloader->isTokenValid('any-token'));
    }
}
