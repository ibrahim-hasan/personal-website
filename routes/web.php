<?php

use App\Http\Controllers\Auth\ReaderEmailVerificationController;
use App\Http\Controllers\Auth\ReaderPasswordResetController;
use App\Http\Controllers\Auth\ReaderRegistrationController;
use App\Http\Controllers\Auth\ReaderSessionController;
use App\Http\Controllers\LanguageSwitchController;
use App\Http\Controllers\Website\AboutController;
use App\Http\Controllers\Website\ArticleController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\PortfolioController;
use App\Http\Controllers\Website\ReaderLibraryController;
use App\Http\Controllers\Website\ReaderNotificationController;
use App\Http\Controllers\Website\ServiceController;
use App\Http\Controllers\Website\WritingController;
use App\Http\Middleware\EnsureReaderIsActive;
use Illuminate\Auth\Middleware\EnsureEmailIsVerified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

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
        Route::get('/reader/library', ReaderLibraryController::class)
            ->middleware(EnsureEmailIsVerified::redirectTo($verificationNoticeRoute))
            ->name('reader.library');
        Route::post('/reader/notifications/{notification}/read', ReaderNotificationController::class)
            ->middleware(EnsureEmailIsVerified::redirectTo($verificationNoticeRoute))
            ->name('reader.notifications.read');
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
