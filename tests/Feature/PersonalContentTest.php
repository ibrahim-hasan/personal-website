<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Database\Seeders\ProjectSeeder;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PersonalContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_current_services_are_seeded_and_drive_the_public_service_content(): void
    {
        $this->seed(ServiceSeeder::class);

        $this->assertSame(4, Service::query()->posted()->count());

        Service::query()
            ->where('slug', 'ai-adoption')
            ->update([
                'name' => ['ar' => 'هندسة ذكاء اصطناعي موثوقة', 'en' => 'Trustworthy AI Engineering'],
            ]);

        app()->setLocale('en');

        $service = collect(SiteContent::services())->firstWhere('id', 'ai-adoption');

        $this->assertSame('Trustworthy AI Engineering', $service['name']);
        $this->get('/en/services')
            ->assertOk()
            ->assertSee('Trustworthy AI Engineering');
    }

    public function test_current_projects_are_seeded_and_drive_the_work_atlas(): void
    {
        $this->seed(ProjectSeeder::class);

        $this->assertSame(8, Project::query()->published()->count());
        $this->assertSame(5, Project::query()->published()->where('featured', true)->count());

        Project::query()
            ->where('slug', 'digi-pedia')
            ->update([
                'title' => ['ar' => 'ديجي بيديا المحدثة', 'en' => 'Digi-Pedia, Updated'],
            ]);

        app()->setLocale('en');

        $project = collect(PortfolioAtlas::projects())->firstWhere('id', 'digi-pedia');

        $this->assertSame('Digi-Pedia, Updated', $project['title']);
        $this->get('/en/work')
            ->assertOk()
            ->assertSee('Digi-Pedia, Updated');
    }

    public function test_empty_canonical_tables_render_intentional_public_empty_states(): void
    {
        app()->setLocale('en');

        $this->assertSame([], SiteContent::services());
        $this->assertSame([], PortfolioAtlas::projects());

        $this->get('/en/services')
            ->assertOk()
            ->assertSee('The next engagement is being shaped.');
        $this->get('/en/work')
            ->assertOk()
            ->assertSee('New case studies are being prepared.');
        $this->get('/en/writing')
            ->assertOk()
            ->assertSee('The next field note is in progress.');
        $this->get('/en/contact')
            ->assertOk()
            ->assertSee('Not sure yet — start from the problem');
    }

    public function test_bootstrap_seeders_preserve_admin_edits_and_deleted_records(): void
    {
        $this->seed([ServiceSeeder::class, ProjectSeeder::class]);

        $service = Service::query()->where('slug', 'ai-adoption')->firstOrFail();
        $service->update(['name' => ['ar' => 'تحرير محفوظ', 'en' => 'Preserved service edit']]);

        $project = Project::query()->where('slug', 'digi-pedia')->firstOrFail();
        $project->delete();

        $this->seed([ServiceSeeder::class, ProjectSeeder::class]);

        $this->assertSame(
            'Preserved service edit',
            Service::query()->where('slug', 'ai-adoption')->firstOrFail()->getTranslation('name', 'en'),
        );
        $this->assertTrue(Project::withTrashed()->where('slug', 'digi-pedia')->firstOrFail()->trashed());
    }

    public function test_contact_settings_drive_the_public_contact_channels(): void
    {
        Setting::setValue('contact_email', 'projects@example.com', 'contact');
        Setting::setValue('contact_phone', '+90 555 123 45 67', 'contact');
        Setting::setValue('whatsapp_number', '+90 555 765 43 21', 'contact');

        app()->setLocale('en');
        $contact = SiteContent::contact();

        $this->assertSame('projects@example.com', $contact['email']);
        $this->assertSame('mailto:projects@example.com', $contact['channels'][0]['href']);
        $this->assertContains('tel:+905551234567', array_column($contact['channels'], 'href'));
        $this->assertContains('https://wa.me/905557654321', array_column($contact['channels'], 'href'));
    }
}
