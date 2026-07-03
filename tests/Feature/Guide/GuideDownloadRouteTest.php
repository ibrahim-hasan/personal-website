<?php

namespace Tests\Feature\Guide;

use App\Models\Guide;
use App\Models\GuideDownloader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GuideDownloadRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_token_downloads_private_guide_file_and_marks_token_used(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('guides/ibrahim-guide.pdf', 'pdf-content');

        $guide = Guide::factory()->create();
        $guide->addMediaFromDisk('guides/ibrahim-guide.pdf', 'local')
            ->usingFileName('ibrahim-guide.pdf')
            ->toMediaCollection('guide_file');

        $downloader = GuideDownloader::factory()->create(['guide_id' => $guide->id]);
        $token = $downloader->generateDownloadToken();

        $this->get(route('guide.download', $token))
            ->assertOk()
            ->assertDownload('ibrahim-guide.pdf');

        $this->assertNotNull($downloader->fresh()->token_used_at);
    }

    public function test_invalid_token_redirects_home_with_error(): void
    {
        $response = $this->get(route('guide.download', 'invalid-token'));

        $response
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', __('guide.link_expired'));
    }

    public function test_missing_guide_file_redirects_home_with_error_without_consuming_token(): void
    {
        $guide = Guide::factory()->create();

        $downloader = GuideDownloader::factory()->create(['guide_id' => $guide->id]);
        $token = $downloader->generateDownloadToken();

        $this->get(route('guide.download', $token))
            ->assertRedirect(route('home'))
            ->assertSessionHas('error', __('guide.file_unavailable'));

        $this->assertNull($downloader->fresh()->token_used_at);
    }
}
