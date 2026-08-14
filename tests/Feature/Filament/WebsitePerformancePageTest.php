<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\WebsitePerformance;
use App\Models\User;
use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class WebsitePerformancePageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([PermissionSeeder::class, RoleSeeder::class]);
        Storage::fake('local');
        config()->set('services.website_performance.snapshot_disk', 'local');
        config()->set('services.website_performance.snapshot_directory', 'website-performance');
        config()->set('filesystems.disks.local.root', storage_path('app/private'));
        app()->setLocale('en');
    }

    public function test_a_super_admin_can_read_safe_aggregate_report_summaries_without_raw_source_values(): void
    {
        $this->snapshots()->persist($this->report('2026-08-09T09:00:00+00:00', 7, 11, 1));
        $this->snapshots()->persist($this->report('2026-08-10T09:00:00+00:00', 11, 17, 3));
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->bootAdminPanel();

        $this->actingAs($superAdmin)
            ->get(WebsitePerformance::getUrl())
            ->assertOk()
            ->assertSee('Website performance')
            ->assertSee('Private aggregate reporting')
            ->assertSee('Valid inquiries')
            ->assertSee('Organic clicks')
            ->assertSee('Consented high-intent activity')
            ->assertSee('Data quality and indexing health')
            ->assertSee('Some GA4 report data was unavailable.')
            ->assertSee('Canonical and indexing checks')
            ->assertSee('Saved report history')
            ->assertSee('2026-08-10T09:00:00+00:00', escape: false)
            ->assertSee('2026-08-09T09:00:00+00:00', escape: false)
            ->assertDontSee('private@example.test')
            ->assertDontSee('server-only-secret')
            ->assertDontSee('https://ibrahimhasan.net/private-path');

        $component = Livewire::actingAs($superAdmin)
            ->test(WebsitePerformance::class);
        $state = json_encode($component->instance()->latestReport, JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString('private@example.test', $state);
        $this->assertStringNotContainsString('server-only-secret', $state);
        $this->assertStringNotContainsString('https://ibrahimhasan.net/private-path', $state);
    }

    public function test_an_admin_who_can_access_the_panel_but_is_not_a_super_admin_cannot_view_reports(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->bootAdminPanel();

        $this->actingAs($admin)
            ->get(WebsitePerformance::getUrl())
            ->assertForbidden();
    }

    public function test_a_super_admin_sees_a_safe_empty_state_when_no_snapshot_has_been_saved(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->bootAdminPanel();

        $this->actingAs($superAdmin)
            ->get(WebsitePerformance::getUrl())
            ->assertOk()
            ->assertSee('No saved reports yet')
            ->assertDontSee('Saved report history');
    }

    public function test_the_page_is_localized_for_arabic_administration(): void
    {
        app()->setLocale('ar');
        $this->snapshots()->persist($this->report('2026-08-10T09:00:00+00:00', 11, 17, 3));
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->bootAdminPanel();

        $this->actingAs($superAdmin)
            ->get(WebsitePerformance::getUrl())
            ->assertOk()
            ->assertSee('أداء الموقع')
            ->assertSee('تقارير مجمّعة وخاصة')
            ->assertSee('طلبات الاستشارة')
            ->assertSee('جودة البيانات وصحة الفهرسة')
            ->assertSee('سجل التقارير المحفوظة')
            ->assertDontSee('Website performance');
    }

    public function test_a_snapshot_configuration_problem_displays_a_safe_unavailable_state(): void
    {
        config()->set('services.website_performance.snapshot_disk', 'public');
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('super_admin');

        $this->bootAdminPanel();

        $this->actingAs($superAdmin)
            ->get(WebsitePerformance::getUrl())
            ->assertOk()
            ->assertSee('Saved reports are unavailable')
            ->assertDontSee('snapshot_configuration_unavailable');
    }

    private function snapshots(): WebsitePerformanceSnapshotStore
    {
        return app(WebsitePerformanceSnapshotStore::class);
    }

    /**
     * @return array<string, mixed>
     */
    private function report(string $generatedAt, int $inquiries, int $sessions, int $ctaClicks): array
    {
        return [
            'schema_version' => 1,
            'generated_at' => $generatedAt,
            'timezone' => 'Asia/Riyadh',
            'data_cutoff' => '2026-08-09',
            'status' => 'partial',
            'periods' => [
                'current' => ['start' => '2026-07-13', 'end' => '2026-08-09'],
                'previous' => ['start' => '2026-06-15', 'end' => '2026-07-12'],
                'context_90d' => ['start' => '2026-05-12', 'end' => '2026-08-09'],
            ],
            'sources' => [
                'first_party' => [
                    'status' => 'ok',
                    'fresh_through' => '2026-08-09',
                    'warnings' => [],
                    'current' => ['inquiries' => ['total' => $inquiries, 'responded' => $inquiries - 1, 'response_rate' => ($inquiries - 1) / $inquiries]],
                    'previous' => ['inquiries' => ['total' => 5, 'responded' => 4, 'response_rate' => 0.8]],
                    'context_90d' => ['inquiries' => ['total' => 20, 'responded' => 18, 'response_rate' => 0.9]],
                ],
                'ga4' => [
                    'status' => 'partial',
                    'fresh_through' => '2026-08-09',
                    'warnings' => ['ga4_current_landing_pages_unavailable'],
                    'current' => [
                        'totals' => ['sessions' => $sessions, 'engagedSessions' => 14, 'eventCount' => 30],
                        'events' => [
                            'available' => true,
                            'rows' => [
                                ['event_name' => 'primary_cta_click', 'eventCount' => $ctaClicks],
                                ['event_name' => 'service_cta_click', 'eventCount' => 2],
                                ['event_name' => 'consultation_form_start', 'eventCount' => 4],
                                ['event_name' => 'consultation_submit_success', 'eventCount' => 2],
                            ],
                        ],
                    ],
                    'previous' => [
                        'totals' => ['sessions' => 10, 'engagedSessions' => 8, 'eventCount' => 19],
                        'events' => [
                            'available' => true,
                            'rows' => [
                                ['event_name' => 'primary_cta_click', 'eventCount' => 1],
                                ['event_name' => 'consultation_form_start', 'eventCount' => 2],
                                ['event_name' => 'consultation_submit_success', 'eventCount' => 1],
                            ],
                        ],
                    ],
                    'context_90d' => ['totals' => ['sessions' => 55, 'engagedSessions' => 42, 'eventCount' => 100]],
                ],
                'search_console' => [
                    'status' => 'ok',
                    'fresh_through' => '2026-08-09',
                    'warnings' => [],
                    'current' => [
                        'totals' => ['clicks' => 19, 'impressions' => 120],
                        'queries' => ['available' => true, 'rows' => [['query' => 'private@example.test', 'clicks' => 1]]],
                        'pages' => ['available' => true, 'rows' => [['page' => 'https://ibrahimhasan.net/private-path', 'clicks' => 1]]],
                        'opaque_token' => 'server-only-secret',
                    ],
                    'previous' => ['totals' => ['clicks' => 12, 'impressions' => 90]],
                    'context_90d' => ['totals' => ['clicks' => 55, 'impressions' => 300]],
                ],
            ],
        ];
    }

    private function bootAdminPanel(): void
    {
        filament()->setCurrentPanel(filament()->getPanel('admin'));
        filament()->bootCurrentPanel();
    }
}
