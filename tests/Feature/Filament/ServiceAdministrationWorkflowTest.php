<?php

namespace Tests\Feature\Filament;

use App\Actions\Services\SetServicePublication;
use App\Filament\Resources\Services\Pages\CreateService;
use App\Filament\Resources\Services\Pages\EditService;
use App\Filament\Resources\Services\Pages\ListServices;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceAdministrationWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }

    public function test_service_form_exposes_the_bilingual_publication_fields_and_preserves_the_stable_key_on_edit(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->create(['key' => 'stable-service-key']);

        Livewire::actingAs($admin)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->assertFormFieldExists('fit_signals.ar')
            ->assertFormFieldExists('fit_signals.en')
            ->assertFormFieldExists('engagement_note.ar')
            ->assertFormFieldExists('engagement_note.en')
            ->assertFormFieldExists('seo_title.ar')
            ->assertFormFieldExists('seo_description.en')
            ->assertFormFieldExists('projects')
            ->assertFormFieldExists('articles')
            ->fillForm(['key' => 'attempted-key-change'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('stable-service-key', $service->fresh()->key);
    }

    public function test_related_projects_save_in_the_operator_selected_order(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->draft()->inactive()->create();
        $firstProject = Project::factory()->create(['key' => 'first-related-project']);
        $secondProject = Project::factory()->create(['key' => 'second-related-project']);

        Livewire::actingAs($admin)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'projects' => [$secondProject->getKey(), $firstProject->getKey()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [$secondProject->key, $firstProject->key],
            $service->fresh()->projects()->pluck('key')->all(),
        );
    }

    public function test_related_articles_save_in_the_operator_selected_order(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->create();
        $firstArticle = Article::factory()->create(['key' => 'first-related-service-article']);
        $secondArticle = Article::factory()->create(['key' => 'second-related-service-article']);

        Livewire::actingAs($admin)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->fillForm([
                'articles' => [$secondArticle->getKey(), $firstArticle->getKey()],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            [$secondArticle->key, $firstArticle->key],
            $service->fresh()->articles()->pluck('key')->all(),
        );
    }

    public function test_authorized_admin_can_publish_and_unpublish_a_complete_service_through_the_filament_actions(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->draft()->inactive()->create();

        Livewire::actingAs($admin)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->assertActionVisible('preview_service')
            ->assertActionVisible('publish_service')
            ->assertActionHidden('unpublish_service')
            ->callAction('publish_service')
            ->assertHasNoActionErrors();

        $service->refresh();

        $this->assertTrue($service->is_active);
        $this->assertFalse($service->is_draft);

        Livewire::actingAs($admin)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->assertActionHidden('publish_service')
            ->assertActionVisible('unpublish_service')
            ->callAction('unpublish_service')
            ->assertHasNoActionErrors();

        $service->refresh();

        $this->assertFalse($service->is_active);
        $this->assertTrue($service->is_draft);
    }

    public function test_creation_and_editing_cannot_bypass_the_publication_action(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $draft = Service::factory()->draft()->inactive()->create();
        $published = Service::factory()->create();

        Livewire::actingAs($editor)
            ->test(EditService::class, ['record' => $draft->getKey()])
            ->set('data.is_draft', false)
            ->set('data.is_active', true)
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($draft->fresh()->is_draft);
        $this->assertFalse($draft->fresh()->is_active);

        Livewire::actingAs($editor)
            ->test(EditService::class, ['record' => $published->getKey()])
            ->fillForm([
                'fit_signals' => [
                    'ar' => [['signal' => 'إشارة واحدة فقط']],
                    'en' => [['signal' => 'Only one signal']],
                ],
            ])
            ->call('save')
            ->assertHasFormErrors();

        $this->assertCount(2, $published->fresh()->getTranslation('fit_signals', 'ar', false));

        Livewire::actingAs($editor)
            ->test(CreateService::class)
            ->fillForm([
                'key' => 'operator-created-service',
                'name' => ['ar' => 'خدمة جديدة', 'en' => 'A new service'],
                'slug' => ['ar' => 'خدمة-جديدة', 'en' => 'new-service'],
                'summary' => ['ar' => 'ملخص واضح للخدمة الجديدة.', 'en' => 'A clear summary for the new Service.'],
                'problem' => ['ar' => 'مشكلة تشغيلية تحتاج إلى ترتيب.', 'en' => 'An operational problem that needs structure.'],
                'approach' => ['ar' => 'منهج عملي يبدأ من فهم العمل.', 'en' => 'A practical approach that starts with the work.'],
                'result' => ['ar' => 'نتيجة عملية يمكن متابعتها.', 'en' => 'A practical result that can be followed through.'],
                'fit_signals' => [
                    'ar' => [
                        ['signal' => 'تحتاج إلى قرار أوضح'],
                        ['signal' => 'تريد ترتيب الأولويات'],
                    ],
                    'en' => [
                        ['signal' => 'You need a clearer decision'],
                        ['signal' => 'You want ordered priorities'],
                    ],
                ],
                'engagement_note' => [
                    'ar' => 'نبدأ بجلسة لفهم التحدّي وتحديد الخطوة التالية.',
                    'en' => 'We begin by understanding the challenge and agreeing the next step.',
                ],
                'seo_title' => ['ar' => 'خدمة جديدة', 'en' => 'A new Service'],
                'seo_description' => [
                    'ar' => 'وصف واضح لخدمة جديدة تناسب حاجة عملية.',
                    'en' => 'A clear description of a new Service for a practical need.',
                ],
                'deliverables' => [
                    ['ar' => 'مخرج واضح', 'en' => 'A clear deliverable'],
                ],
                'is_draft' => false,
                'is_active' => true,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = Service::query()->where('key', 'operator-created-service')->firstOrFail();

        $this->assertTrue($created->is_draft);
        $this->assertFalse($created->is_active);
        $this->assertSame(
            ['تحتاج إلى قرار أوضح', 'تريد ترتيب الأولويات'],
            $created->getTranslation('fit_signals', 'ar', false),
        );
    }

    public function test_incomplete_drafts_remain_editable_until_an_authorized_operator_publishes_them(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $draft = Service::factory()->draft()->inactive()->create([
            'summary' => ['ar' => '', 'en' => ''],
            'problem' => ['ar' => '', 'en' => ''],
            'approach' => ['ar' => '', 'en' => ''],
            'result' => ['ar' => '', 'en' => ''],
            'fit_signals' => ['ar' => [], 'en' => []],
            'engagement_note' => ['ar' => '', 'en' => ''],
            'seo_title' => ['ar' => '', 'en' => ''],
            'seo_description' => ['ar' => '', 'en' => ''],
        ]);

        Livewire::actingAs($editor)
            ->test(EditService::class, ['record' => $draft->getKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($draft->fresh()->is_draft);
        $this->assertFalse($draft->fresh()->is_active);
    }

    public function test_editor_can_preview_but_cannot_publish_or_unpublish_a_service(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole('editor');
        $service = Service::factory()->draft()->inactive()->create();

        Livewire::actingAs($editor)
            ->test(EditService::class, ['record' => $service->getKey()])
            ->assertActionVisible('preview_service')
            ->assertActionHidden('publish_service')
            ->assertActionHidden('unpublish_service');
    }

    public function test_service_table_uses_a_status_badge_and_governed_record_actions_instead_of_inline_toggles(): void
    {
        $admin = $this->admin();
        $draft = Service::factory()->draft()->inactive()->create();
        $published = Service::factory()->create();

        Livewire::actingAs($admin)
            ->test(ListServices::class)
            ->assertTableColumnExists('publication_status')
            ->assertTableActionVisible('preview_service', $draft)
            ->assertTableActionVisible('publish_service', $draft)
            ->assertTableActionHidden('unpublish_service', $draft)
            ->assertTableActionVisible('unpublish_service', $published)
            ->assertTableActionHidden('publish_service', $published);
    }

    public function test_preview_is_policy_gated_and_uses_the_public_service_presentation_without_publication_scope(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->draft()->inactive()->create();
        $previewUrl = route('filament.admin.services.preview', ['service' => $service->getKey()]);

        $this->get(localized_route('services.show', ['service' => $service], locale: 'ar'))
            ->assertNotFound();

        $previewResponse = $this->actingAs($admin)
            ->get($previewUrl)
            ->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, noarchive')
            ->assertSee($service->getTranslation('name', 'ar'))
            ->assertDontSee('google-analytics-id', false);

        $this->assertStringContainsString('private', (string) $previewResponse->headers->get('Cache-Control'));
        $this->assertStringContainsString('no-store', (string) $previewResponse->headers->get('Cache-Control'));

        $viewer = User::factory()->create();
        $viewer->givePermissionTo('view services');

        $this->actingAs($viewer)
            ->get($previewUrl)
            ->assertForbidden();
    }

    public function test_publication_failures_are_specific_and_localized_for_each_admin_locale(): void
    {
        $admin = $this->admin();
        $service = Service::factory()->draft()->inactive()->create([
            'fit_signals' => [
                'ar' => ['إشارة واحدة فقط'],
                'en' => ['Only one signal'],
            ],
        ]);

        foreach (['ar', 'en'] as $locale) {
            app()->setLocale($locale);

            try {
                app(SetServicePublication::class)->publish($admin, $service);
                $this->fail('The service must not publish with fewer than two fit signals.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    __('service_admin.publication.fit_signals', [
                        'locale' => __('service_admin.locales.'.$locale),
                    ]),
                    $exception->errors()["fit_signals.{$locale}"][0],
                );
            }
        }
    }

    private function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        return $user;
    }
}
