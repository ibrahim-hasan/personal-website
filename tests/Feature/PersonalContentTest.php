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

    public function test_data_governance_problem_copy_uses_the_correct_arabic_wording(): void
    {
        $service = collect(SiteContent::serviceDefaults())->firstWhere('id', 'data-governance');

        $this->assertSame(
            'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودة لا تكفي لاتخاذ قرارات موثوقة.',
            $service['problem']['ar'],
        );
    }

    public function test_process_copy_keeps_the_digital_decision_concise_in_both_locales(): void
    {
        app()->setLocale('ar');

        $arabicStep = collect(SiteContent::process())->firstWhere('step', '03');

        $this->assertSame('تحديد دور الرقمنة والأتمتة والذكاء الاصطناعي', $arabicStep['title']);
        $this->assertSame('نحدد الأنسب: ذكاء اصطناعي، أو نظام أفضل، أو تحسين جودة البيانات؛ بحسب الأثر والكلفة والمخاطر.', $arabicStep['body']);

        app()->setLocale('en');

        $englishStep = collect(SiteContent::process())->firstWhere('step', '03');

        $this->assertSame('Choose the role of systems, automation, and AI', $englishStep['title']);
        $this->assertSame('Choose between AI, a better system, and improving data quality based on impact, cost, and risk.', $englishStep['body']);
    }

    public function test_data_governance_copy_migration_is_reversible_and_preserves_english(): void
    {
        $service = Service::factory()->create([
            'key' => 'data-governance',
            'problem' => [
                'ar' => 'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودود لا يكفي لقرارات موثوقة.',
                'en' => 'Scattered data with insufficient quality.',
            ],
        ]);
        $migration = require database_path('migrations/2026_07_16_192650_fix_data_governance_arabic_problem_copy.php');

        $migration->up();
        $service->refresh();

        $this->assertSame(
            'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودة لا تكفي لاتخاذ قرارات موثوقة.',
            $service->getTranslation('problem', 'ar'),
        );
        $this->assertSame('Scattered data with insufficient quality.', $service->getTranslation('problem', 'en'));

        $migration->down();
        $service->refresh();

        $this->assertSame(
            'بيانات متناثرة، ملكية غامضة، صلاحيات غير واضحة، وجودود لا يكفي لقرارات موثوقة.',
            $service->getTranslation('problem', 'ar'),
        );
        $this->assertSame('Scattered data with insufficient quality.', $service->getTranslation('problem', 'en'));
    }

    public function test_current_services_are_seeded_and_drive_the_public_service_content(): void
    {
        $this->seed(ServiceSeeder::class);

        $this->assertSame(4, Service::query()->posted()->count());

        Service::query()
            ->where('key', 'ai-adoption')
            ->update([
                'name' => ['ar' => 'هندسة ذكاء اصطناعي موثوقة', 'en' => 'Trustworthy AI Engineering'],
            ]);

        app()->setLocale('en');

        $service = collect(SiteContent::services())->firstWhere('key', 'ai-adoption');

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

        $seededProject = Project::query()->where('key', 'digi-pedia')->firstOrFail();

        $this->assertSame('الموسوعة الرقمية', $seededProject->getTranslation('title', 'ar'));
        $this->assertSame('Digi Pedia', $seededProject->getTranslation('title', 'en'));
        $this->assertSame('شعار الموسوعة الرقمية', $seededProject->getTranslation('logo_alt', 'ar'));
        $this->assertSame('Digi Pedia logo', $seededProject->getTranslation('logo_alt', 'en'));
        $this->assertSame('ديجي-بيديا', $seededProject->getTranslation('slug', 'ar'));
        $this->assertSame('digi-pedia', $seededProject->getTranslation('slug', 'en'));

        Project::query()
            ->where('key', 'digi-pedia')
            ->update([
                'title' => ['ar' => 'ديجي بيديا المحدثة', 'en' => 'Digi-Pedia, Updated'],
            ]);

        app()->setLocale('en');

        $project = collect(PortfolioAtlas::projects())->firstWhere('key', 'digi-pedia');

        $this->assertSame('Digi-Pedia, Updated', $project['title']);
        $this->get('/en/work')
            ->assertOk()
            ->assertSee('Digi-Pedia, Updated');

        $this->get('/en')
            ->assertOk()
            ->assertSee('class="project-atlas__identity"', false)
            ->assertSee('class="project-brand project-brand--digi-pedia"', false)
            ->assertSee('alt="Digi Pedia logo"', false);
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

    public function test_project_display_name_migration_is_reversible_without_changing_localized_slugs(): void
    {
        $project = Project::factory()->create([
            'key' => 'digi-pedia',
            'slug' => ['ar' => 'ديجي-بيديا', 'en' => 'digi-pedia'],
            'title' => ['ar' => 'ديجي بيديا', 'en' => 'Digi-Pedia'],
            'image_alt' => ['ar' => 'تجربة ديجي بيديا لتعلّم الذكاء الاصطناعي بالعربية', 'en' => 'Digi-Pedia Arabic AI learning experience'],
            'logo_alt' => ['ar' => 'شعار ديجي بيديا', 'en' => 'Digi-Pedia logo'],
        ]);
        $migration = require database_path('migrations/2026_07_16_162516_update_digi_pedia_display_name.php');

        $migration->up();
        $project->refresh();

        $this->assertSame('الموسوعة الرقمية', $project->getTranslation('title', 'ar'));
        $this->assertSame('Digi Pedia', $project->getTranslation('title', 'en'));
        $this->assertSame('ديجي-بيديا', $project->getTranslation('slug', 'ar'));
        $this->assertSame('digi-pedia', $project->getTranslation('slug', 'en'));

        $migration->down();
        $project->refresh();

        $this->assertSame('ديجي بيديا', $project->getTranslation('title', 'ar'));
        $this->assertSame('Digi-Pedia', $project->getTranslation('title', 'en'));
        $this->assertSame('ديجي-بيديا', $project->getTranslation('slug', 'ar'));
        $this->assertSame('digi-pedia', $project->getTranslation('slug', 'en'));
    }

    public function test_bootstrap_seeders_preserve_admin_edits_and_deleted_records(): void
    {
        $this->seed([ServiceSeeder::class, ProjectSeeder::class]);

        $service = Service::query()->where('key', 'ai-adoption')->firstOrFail();
        $service->update(['name' => ['ar' => 'تحرير محفوظ', 'en' => 'Preserved service edit']]);

        $project = Project::query()->where('key', 'digi-pedia')->firstOrFail();
        $project->delete();

        $this->seed([ServiceSeeder::class, ProjectSeeder::class]);

        $this->assertSame(
            'Preserved service edit',
            Service::query()->where('key', 'ai-adoption')->firstOrFail()->getTranslation('name', 'en'),
        );
        $this->assertTrue(Project::withTrashed()->where('key', 'digi-pedia')->firstOrFail()->trashed());
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
        $this->assertSame('ltr', $contact['channels'][0]['value_direction']);
        $this->assertContains('tel:+905551234567', array_column($contact['channels'], 'href'));
        $this->assertContains('https://wa.me/905557654321', array_column($contact['channels'], 'href'));
        $this->assertContains('Response time', array_column($contact['channels'], 'label'));
        $this->assertContains('Usually within one business day', array_column($contact['channels'], 'value'));
        $this->assertNotContains('Location', array_column($contact['channels'], 'label'));

        $phoneChannel = collect($contact['channels'])->firstWhere('href', 'tel:+905551234567');

        $this->assertSame('ltr', $phoneChannel['value_direction']);

        app()->setLocale('ar');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('<bdi dir="ltr">+90 555 123 45 67</bdi>', false);
    }
}
