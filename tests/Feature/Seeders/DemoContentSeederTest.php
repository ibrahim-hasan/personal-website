<?php

namespace Tests\Feature\Seeders;

use App\Enums\IntellectualLibraryType;
use App\Models\Author;
use App\Models\Guide;
use App\Models\GuideDownloader;
use App\Models\IntellectualLibrary;
use App\Models\Newsletter;
use App\Models\Service;
use App\Models\Setting;
use Database\Seeders\DemoContentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoContentSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_content_seeder_populates_realistic_public_site_content(): void
    {
        $this->seed(DemoContentSeeder::class);
        $this->seed(DemoContentSeeder::class);

        $this->assertSame('Ibrahim Hasan', Setting::getValue('site_name_en', 'site'));
        $this->assertSame('hello@ibrahimhasan.net', Setting::getValue('contact_email', 'contact'));
        $this->assertSame('180+', Setting::getValue('home_stats_total_clients', 'stats'));

        $this->assertSame(2, Author::query()->count());
        $this->assertSame(4, Service::query()->posted()->count());
        $this->assertSame(3, Guide::query()->posted()->count());
        $this->assertSame(12, IntellectualLibrary::query()->posted()->count());
        $this->assertSame(36, GuideDownloader::query()->count());
        $this->assertSame(48, Newsletter::query()->count());

        foreach (IntellectualLibraryType::cases() as $type) {
            $this->assertTrue(
                IntellectualLibrary::query()->posted()->where('type', $type)->exists(),
                "Expected at least one {$type->value} demo library item.",
            );
        }

        $this->assertTrue(Guide::query()->firstOrFail()->hasMedia('guide_file'));
        $this->assertTrue(IntellectualLibrary::query()->where('type', IntellectualLibraryType::Tool)->firstOrFail()->hasMedia('tool_file'));
    }
}
