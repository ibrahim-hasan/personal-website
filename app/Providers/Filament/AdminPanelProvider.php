<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login as AdminLogin;
use App\Filament\Auth\RequestPasswordReset as AdminRequestPasswordReset;
use App\Filament\Auth\ResetPassword as AdminResetPassword;
use App\Filament\Pages\Profile;
use App\Filament\Widgets\AdminContentStats;
use App\Http\Controllers\Filament\GenerateArticleAudioController;
use App\Http\Controllers\Filament\GenerateArticleAudioSampleController;
use App\Http\Controllers\Filament\PrepareArticleNarrationController;
use App\Http\Controllers\Filament\UpdateArticleNarrationController;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(AdminLogin::class)
            ->passwordReset(AdminRequestPasswordReset::class, AdminResetPassword::class)
            ->brandName(__('admin.brand.name'))
            ->brandLogo(fn () => view('filament.partials.auth-brand-logo'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('images/ibrahim/ibrahim-systems-portrait-compact.webp'))
            ->font(
                'Agt Rafeeq Sans',
                url: asset('fonts/agt-rafeeq/admin-font.css'),
                provider: LocalFontProvider::class,
            )
            ->colors([
                'primary' => [
                    50 => 'oklch(0.96 0.022 300)',
                    100 => 'oklch(0.91 0.05 300)',
                    200 => 'oklch(0.84 0.08 299)',
                    300 => 'oklch(0.74 0.115 298)',
                    400 => 'oklch(0.64 0.155 297)',
                    500 => 'oklch(0.55 0.18 296)',
                    600 => 'oklch(0.47 0.18 295)',
                    700 => 'oklch(0.39 0.155 294)',
                    800 => 'oklch(0.29 0.11 293)',
                    900 => 'oklch(0.205 0.065 292)',
                    950 => 'oklch(0.155 0.04 292)',
                ],
                'gray' => Color::Slate,
                'danger' => Color::Rose,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->collapsibleNavigationGroups(false)
            ->sidebarWidth('17rem')
            ->maxContentWidth('full')
            ->simplePageMaxContentWidth('full')
            ->darkMode(true)
            ->viteTheme('resources/css/filament/admin/ibrahim.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.content')),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.engagement')),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.administration')),
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.partials.topbar-language-switcher'),
            )
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AdminContentStats::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->routes(function (): void {
                Route::post('/article-audio/{article}/{locale}/generate', GenerateArticleAudioController::class)
                    ->middleware([Authenticate::class, 'throttle:6,1'])
                    ->name('article-audio.generate');

                Route::post('/article-audio/{article}/{locale}/narration/prepare', PrepareArticleNarrationController::class)
                    ->middleware([Authenticate::class, 'throttle:3,1'])
                    ->name('article-audio.narration.prepare');

                Route::put('/article-audio/{article}/{locale}/narration', UpdateArticleNarrationController::class)
                    ->middleware([Authenticate::class, 'throttle:20,1'])
                    ->name('article-audio.narration.update');

                Route::post('/article-audio/{article}/{locale}/sample', GenerateArticleAudioSampleController::class)
                    ->middleware([Authenticate::class, 'throttle:6,1'])
                    ->name('article-audio.sample.generate');
            })
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => Profile::getLabel())
                    ->url(fn (): string => Profile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ]);
    }
}
