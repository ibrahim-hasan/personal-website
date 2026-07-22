<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\ManageArticleAudio;
use App\Filament\Pages\ManageSiteSettings;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Comments\CommentResource;
use App\Filament\Resources\ContactInquiries\ContactInquiryResource;
use App\Filament\Resources\Projects\ProjectResource;
use App\Filament\Resources\Roles\RoleResource;
use App\Filament\Resources\Services\ServiceResource;
use App\Filament\Resources\Users\UserResource;
use Filament\FontProviders\LocalFontProvider;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class AdminBrandingTest extends TestCase
{
    public function test_admin_login_uses_ibrahim_branding_without_legacy_assets(): void
    {
        App::setLocale('ar');

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(__('admin.brand.owner'))
            ->assertSee('لوحة تحكم')
            ->assertDontSee('لوحة تحكم موقع إبراهيم حسن')
            ->assertSee('ibrahim-admin-wordmark.svg', escape: false)
            ->assertDontSee('manage-layers-section-bg.svg', escape: false)
            ->assertDontSee('logo-dark.svg', escape: false);
    }

    public function test_password_recovery_uses_the_same_admin_shell(): void
    {
        $this->get('/admin/password-reset/request')
            ->assertOk()
            ->assertSee(__('admin.auth.forgot_password'))
            ->assertSee(__('admin.brand.subtitle'))
            ->assertDontSee('manage-layers-section-bg.svg', escape: false);
    }

    public function test_admin_loads_the_exact_rafeeq_font_family_and_weight_map(): void
    {
        $panel = filament()->getPanel('admin');
        $theme = file_get_contents(resource_path('css/filament/admin/ibrahim.css'));
        $fontStylesheetPath = public_path('fonts/agt-rafeeq/admin-font.css');
        $fontStylesheet = file_get_contents($fontStylesheetPath);

        $this->assertNotFalse($theme);
        $this->assertNotFalse($fontStylesheet);

        App::setLocale('en');

        $this->assertSame('Agt Rafeeq Sans', $panel->getFontFamily());
        $this->assertSame(LocalFontProvider::class, $panel->getFontProvider());
        $this->assertSame(asset('fonts/agt-rafeeq/admin-font.css'), $panel->getFontUrl());
        $this->assertStringContainsString(asset('fonts/agt-rafeeq/admin-font.css'), $panel->getFontHtml()->toHtml());
        $this->assertSame('resources/css/filament/admin/ibrahim.css', $panel->getViteTheme());

        App::setLocale('ar');

        $this->assertSame('Agt Rafeeq Sans', $panel->getFontFamily());
        $this->assertStringContainsString('--font-family: "Agt Rafeeq Sans"', $theme);
        $this->assertStringNotContainsString('GhroobArabic', $theme);
        $this->assertStringNotContainsString('font-size: 14px;', $theme);
        $this->assertStringContainsString("url('../../../../public/images/brand/ibrahim-geometric-pattern.svg')", $theme);
        $this->assertMatchesRegularExpression(
            '/\.fi-auth-shell__context\s*\{[^}]*background:\s*var\(--admin-nav-bg\);/s',
            $theme,
        );
        $this->assertDoesNotMatchRegularExpression('/\.fi-panel-admin\s+\*\s*\{[^}]*font-family/s', $theme);
        $this->assertStringContainsString("font-family: 'Agt Rafeeq Sans';", $fontStylesheet);

        foreach ([200, 300, 400, 500, 700, 800, 900] as $fontWeight) {
            $this->assertStringContainsString("font-weight: {$fontWeight};", $fontStylesheet);
        }

        foreach ($this->rafeeqAdminFontFiles() as $fontFile) {
            $this->assertFileExists($fontFile);
            $this->assertGreaterThan(0, filesize($fontFile));
        }

        $this->get('/admin/login')
            ->assertOk()
            ->assertSee(asset('fonts/agt-rafeeq/admin-font.css'), escape: false);
    }

    public function test_admin_navigation_is_compact_and_non_collapsible(): void
    {
        $panel = filament()->getPanel('admin');

        App::setLocale('en');

        $this->assertSame(
            ['Content', 'Engagement', 'Administration'],
            array_map(
                static fn ($group): ?string => $group->getLabel(),
                $panel->getNavigationGroups(),
            ),
        );
        $this->assertFalse($panel->hasCollapsibleNavigationGroups());
        $this->assertTrue($panel->isSidebarCollapsibleOnDesktop());
        $this->assertSame('17rem', $panel->getSidebarWidth());
    }

    public function test_admin_navigation_items_follow_a_stable_task_order(): void
    {
        App::setLocale('en');

        $navigationItems = [
            ProjectResource::class => ['Content', 10],
            ServiceResource::class => ['Content', 20],
            ArticleResource::class => ['Content', 30],
            ManageArticleAudio::class => ['Content', 40],
            ContactInquiryResource::class => ['Engagement', 10],
            CommentResource::class => ['Engagement', 20],
            ManageSiteSettings::class => ['Administration', 10],
            UserResource::class => ['Administration', 20],
            RoleResource::class => ['Administration', 30],
        ];

        foreach ($navigationItems as $navigationItem => [$group, $sort]) {
            $this->assertSame($group, $navigationItem::getNavigationGroup());
            $this->assertSame($sort, $navigationItem::getNavigationSort());
        }
    }

    public function test_admin_theme_styles_sidebar_labels_directly(): void
    {
        $theme = file_get_contents(resource_path('css/filament/admin/ibrahim.css'));

        $this->assertNotFalse($theme);
        $this->assertStringContainsString('.fi-panel-admin .fi-sidebar-item-label', $theme);
        $this->assertStringContainsString('color: var(--admin-nav-text);', $theme);
        $this->assertStringContainsString('.fi-sidebar-item.fi-active > .fi-sidebar-item-btn > .fi-sidebar-item-label', $theme);
        $this->assertStringContainsString('min-height: 2.75rem;', $theme);
        $this->assertStringContainsString('.dashboard-home-stat .fi-wi-stats-overview-stat-value', $theme);
        $this->assertStringNotContainsString('.dashboard-home-stat::before', $theme);
        $this->assertStringContainsString('.fi-section:not(.fi-section-not-contained)', $theme);
        $this->assertStringContainsString('.fi-wi-stats-overview .fi-section-not-contained', $theme);
        $this->assertStringContainsString('.fi-topbar .admin-brand-lockup__wordmark', $theme);
        $this->assertStringContainsString('.fi-ta-empty-state-content', $theme);
        $this->assertStringContainsString('.fi-ta-empty-state-description', $theme);
    }

    public function test_every_admin_resource_table_uses_a_contextual_empty_state(): void
    {
        $tables = [
            'articles' => 'app/Filament/Resources/Articles/Tables/ArticlesTable.php',
            'comments' => 'app/Filament/Resources/Comments/Tables/CommentsTable.php',
            'contact_inquiries' => 'app/Filament/Resources/ContactInquiries/Tables/ContactInquiriesTable.php',
            'projects' => 'app/Filament/Resources/Projects/Tables/ProjectsTable.php',
            'roles' => 'app/Filament/Resources/Roles/Tables/RolesTable.php',
            'services' => 'app/Filament/Resources/Services/Tables/ServicesTable.php',
            'users' => 'app/Filament/Resources/Users/Tables/UsersTable.php',
        ];

        foreach ($tables as $resource => $path) {
            $contents = file_get_contents(base_path($path));

            $this->assertNotFalse($contents);
            $this->assertStringContainsString("AdminTableEmptyState::apply(\$table, '{$resource}'", $contents);
            $this->assertNotSame("admin.empty_states.{$resource}.heading", __("admin.empty_states.{$resource}.heading"));
            $this->assertNotSame("admin.empty_states.{$resource}.description", __("admin.empty_states.{$resource}.description"));
        }
    }

    /**
     * @return array<int, string>
     */
    private function rafeeqAdminFontFiles(): array
    {
        return [
            public_path('fonts/agt-rafeeq/DINNextLTArabic-UltraLight.woff2'),
            public_path('fonts/agt-rafeeq/DINNextLTArabic-Light.woff2'),
            public_path('fonts/agt-rafeeq/DINNextLTArabic-Regular.woff2'),
            public_path('fonts/agt-rafeeq/DINNextLTArabic-Medium.woff2'),
            public_path('fonts/agt-rafeeq/DINNextLTArabic-Bold.woff2'),
            public_path('fonts/agt-rafeeq/DINNextLTArabic-Heavy.woff2'),
            public_path('fonts/agt-rafeeq/DINNextLTArabic-Black.woff2'),
        ];
    }
}
