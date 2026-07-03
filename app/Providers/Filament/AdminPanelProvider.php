<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login as AdminLogin;
use App\Filament\Auth\RequestPasswordReset as AdminRequestPasswordReset;
use App\Filament\Auth\ResetPassword as AdminResetPassword;
use App\Filament\Pages\Profile;
use App\Filament\Widgets\AdminContentStats;
use App\Filament\Widgets\GuideDownloadsChart;
use App\Filament\Widgets\NewsletterJoinsChart;
use App\Http\Controllers\Filament\GuideFileDownloadController;
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
            ->brandLogo(asset('images/logo-dark.svg'))
            ->darkModeBrandLogo(asset('images/logo.svg'))
            ->brandLogoHeight('3.5rem')
            ->favicon(asset('images/favicon.png'))
            ->font('Noto Sans', provider: LocalFontProvider::class)
            ->colors([
                'primary' => [
                    50 => '#FCFBF8',
                    100 => '#F6F4EB',
                    200 => '#F0ECDE',
                    300 => '#E4DDC4',
                    400 => '#D9CEA9',
                    500 => '#C4B37D',
                    600 => '#B4A571',
                    700 => '#9F8F62',
                    800 => '#857A54',
                    900 => '#6C6344',
                    950 => '#585037',
                ],
                'gray' => [
                    50 => '#F5F5F6',
                    100 => '#E3E3E3',
                    200 => '#D0D0D0',
                    300 => '#AAAAAA',
                    400 => '#848484',
                    500 => '#414042',
                    600 => '#3C3B3D',
                    700 => '#343335',
                    800 => '#2C2C2D',
                    900 => '#242324',
                    950 => '#1E1D1E',
                ],
                'danger' => Color::Rose,
                'info' => [
                    50 => '#F4F6F8',
                    100 => '#E9EDF0',
                    200 => '#CBD4DC',
                    300 => '#AAB9C6',
                    400 => '#4B5565',
                    500 => '#414042',
                    600 => '#3C3B3D',
                    700 => '#343335',
                    800 => '#2C2C2D',
                    900 => '#242324',
                    950 => '#1E1D1E',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('17rem')
            ->maxContentWidth('full')
            ->simplePageMaxContentWidth('full')
            ->darkMode(true)
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.content')),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.guides')),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.access')),
                NavigationGroup::make()
                    ->label(fn (): string => __('admin.navigation.configuration')),
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
                GuideDownloadsChart::class,
                NewsletterJoinsChart::class,
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
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => Profile::getLabel())
                    ->url(fn (): string => Profile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->routes(function (): void {
                Route::get('/guides/{guide}/guide-file', GuideFileDownloadController::class)
                    ->name('guides.guide-file');
            })
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn (): string => Profile::getLabel())
                    ->url(fn (): string => Profile::getUrl())
                    ->icon('heroicon-o-user-circle'),
            ]);
    }
}
