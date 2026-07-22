<?php

use App\Http\Controllers\AtharController;
use App\Http\Controllers\Auth\ReaderEmailVerificationController;
use App\Http\Controllers\Auth\ReaderPasswordResetController;
use App\Http\Controllers\Auth\ReaderRegistrationController;
use App\Http\Controllers\Auth\ReaderSessionController;
use App\Http\Controllers\Auth\ReaderTermsAcceptanceController;
use App\Http\Controllers\LanguageSwitchController;
use App\Http\Controllers\Website\AboutController;
use App\Http\Controllers\Website\ArticleController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\LegalController;
use App\Http\Controllers\Website\MarkHeroVideoViewedController;
use App\Http\Controllers\Website\PortfolioController;
use App\Http\Controllers\Website\ReaderAccountController;
use App\Http\Controllers\Website\ReaderAccountDeletionController;
use App\Http\Controllers\Website\ReaderLibraryController;
use App\Http\Controllers\Website\ReaderNotificationController;
use App\Http\Controllers\Website\ReaderPasswordController;
use App\Http\Controllers\Website\RobotsController;
use App\Http\Controllers\Website\ServiceController;
use App\Http\Controllers\Website\SitemapController;
use App\Http\Controllers\Website\WritingController;
use App\Http\Middleware\EnsureReaderAcceptedCurrentTerms;
use App\Http\Middleware\EnsureReaderIsActive;
use App\Http\Middleware\SetLocale;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

$statefulWebMiddleware = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
    SetLocale::class,
];

Route::get('/sitemap.xml', SitemapController::class)
    ->withoutMiddleware($statefulWebMiddleware)
    ->name('seo.sitemap');
Route::get('/robots.txt', RobotsController::class)
    ->withoutMiddleware($statefulWebMiddleware)
    ->name('seo.robots');

Route::post('/lang/{locale}', LanguageSwitchController::class)->name('lang.switch');

Route::get('/login', function (): RedirectResponse {
    return redirect()->to(localized_route('reader.login'));
})->name('login');

$registerLocalizedRoutes = function (?string $routeLocale = null): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/services', [ServiceController::class, 'index'])->name('services');
    Route::get('/work', PortfolioController::class)->name('work');
    Route::get('/writing', WritingController::class)->name('writing');
    Route::get('/writing/{article:slug}', ArticleController::class)->name('writing.show');
    Route::get('/about', AboutController::class)->name('about');
    Route::get('/contact', ContactController::class)->name('contact');
    Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
    Route::get('/cookies', [LegalController::class, 'cookies'])->name('cookies');
    Route::get('/terms', [LegalController::class, 'terms'])->name('terms');

    Route::prefix('/athar/{token}')->group(function (): void {
        Route::get('/', [AtharController::class, 'show'])->name('athar.show');
        Route::post('/code', [AtharController::class, 'requestCode'])->middleware('throttle:athar-code')->name('athar.code');
        Route::post('/verify', [AtharController::class, 'verifyCode'])->middleware('throttle:athar-code')->name('athar.verify');
        Route::post('/draft', [AtharController::class, 'saveDraft'])->middleware('throttle:athar-write')->name('athar.draft');
        Route::post('/submit', [AtharController::class, 'seal'])->middleware('throttle:athar-write')->name('athar.submit');
        Route::post('/approve', [AtharController::class, 'approve'])->middleware('throttle:athar-write')->name('athar.approve');
        Route::post('/withdraw', [AtharController::class, 'withdraw'])->middleware('throttle:athar-write')->name('athar.withdraw');
        Route::post('/deletion', [AtharController::class, 'deletion'])->middleware('throttle:athar-write')->name('athar.deletion');
        Route::post('/deletion/cancel', [AtharController::class, 'cancelDeletion'])->middleware('throttle:athar-write')->name('athar.deletion.cancel');
        Route::post('/restore', [AtharController::class, 'restore'])->middleware('throttle:athar-write')->name('athar.restore');
    });

    Route::middleware('guest')->group(function (): void {
        Route::get('/reader/login', [ReaderSessionController::class, 'create'])->name('reader.login');
        Route::post('/reader/login', [ReaderSessionController::class, 'store'])->name('reader.login.store');
        Route::get('/reader/register', [ReaderRegistrationController::class, 'create'])->name('reader.register');
        Route::post('/reader/register', [ReaderRegistrationController::class, 'store'])
            ->middleware('throttle:3,1')
            ->name('reader.register.store');
        Route::get('/reader/forgot-password', [ReaderPasswordResetController::class, 'create'])
            ->name('reader.password.request');
        Route::post('/reader/forgot-password', [ReaderPasswordResetController::class, 'store'])
            ->middleware('throttle:3,1')
            ->name('reader.password.email');
        Route::get('/reader/reset-password/{token}', [ReaderPasswordResetController::class, 'edit'])
            ->name('reader.password.reset');
        Route::post('/reader/reset-password', [ReaderPasswordResetController::class, 'update'])
            ->middleware('throttle:6,1')
            ->name('reader.password.update');
    });

    Route::get('/reader/email/verify/{id}/{hash}', ReaderEmailVerificationController::class)
        ->middleware(['auth', EnsureReaderIsActive::class, 'signed', 'throttle:6,1'])
        ->name('verification.verify');

    $verificationNoticeRoute = $routeLocale === null
        ? 'reader.verification.notice'
        : $routeLocale.'.reader.verification.notice';

    Route::middleware(['auth', EnsureReaderIsActive::class])->group(function () use ($verificationNoticeRoute): void {
        Route::get('/reader/verify-email', fn () => view('auth.reader-verify-email'))
            ->name('reader.verification.notice');
        Route::post('/reader/email/verification-notification', function (Request $request): RedirectResponse {
            $request->user()->sendEmailVerificationNotification();

            return back()->with('status', 'verification-link-sent');
        })->middleware('throttle:6,1')->name('verification.send');
        Route::post('/reader/logout', [ReaderSessionController::class, 'destroy'])->name('reader.logout');
        Route::get('/reader/terms/accept', [ReaderTermsAcceptanceController::class, 'show'])
            ->name('reader.terms.acceptance');
        Route::post('/reader/terms/accept', [ReaderTermsAcceptanceController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('reader.terms.acceptance.store');
        Route::delete('/reader/account', ReaderAccountDeletionController::class)
            ->middleware('throttle:3,1')
            ->name('reader.account.destroy');

        Route::middleware(EnsureReaderAcceptedCurrentTerms::class)->group(function () use ($verificationNoticeRoute): void {
            Route::post('/reader/hero-video/viewed', MarkHeroVideoViewedController::class)
                ->middleware('throttle:6,1')
                ->name('reader.hero-video.viewed');
            Route::get('/reader/account', [ReaderAccountController::class, 'show'])->name('reader.account');
            Route::patch('/reader/account', [ReaderAccountController::class, 'update'])
                ->middleware('throttle:10,1')
                ->name('reader.account.update');
            Route::put('/reader/account/password', ReaderPasswordController::class)
                ->middleware('throttle:5,1')
                ->name('reader.account.password.update');
            Route::get('/reader/library', ReaderLibraryController::class)
                ->middleware(EnsureEmailIsVerified::redirectTo($verificationNoticeRoute))
                ->name('reader.library');
            Route::post('/reader/notifications/{notification}/read', ReaderNotificationController::class)
                ->middleware(EnsureEmailIsVerified::redirectTo($verificationNoticeRoute))
                ->name('reader.notifications.read');
        });
    });
};

Route::get('/reader/email/verification-required', function (Request $request): RedirectResponse {
    return redirect()->to(localized_route(
        'reader.verification.notice',
        locale: $request->user()->preferredLocale(),
    ));
})->middleware(['auth', EnsureReaderIsActive::class])->name('verification.notice');

$defaultLocale = LaravelLocalization::getDefaultLocale();
$requestLocale = LaravelLocalization::setLocale();

foreach (array_keys(LaravelLocalization::getSupportedLocales()) as $locale) {
    $prefix = $locale === $defaultLocale
        ? null
        : LaravelLocalization::setLocale($locale);

    Route::prefix($prefix)
        ->name($locale === $defaultLocale ? '' : $locale.'.')
        ->middleware(['localize', 'localizationRedirect', 'localeViewPath'])
        ->group(fn () => $registerLocalizedRoutes($locale === $defaultLocale ? null : $locale));
}

LaravelLocalization::setLocale($requestLocale);
