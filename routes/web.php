<?php

use App\Http\Controllers\LanguageSwitchController;
use App\Http\Controllers\Website\AboutController;
use App\Http\Controllers\Website\ContactController;
use App\Http\Controllers\Website\GuideController;
use App\Http\Controllers\Website\HomeController;
use App\Http\Controllers\Website\NewsletterController;
use App\Http\Controllers\Website\PortfolioController;
use App\Http\Controllers\Website\ServiceController;
use App\Http\Controllers\Website\WritingController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;

Route::post('/lang/{locale}', LanguageSwitchController::class)->name('lang.switch');

$localizedMiddleware = [
    LaravelLocalizationViewPath::class,
];

$defaultLocale = app('laravellocalization')->getDefaultLocale();

$registerWebsiteRoutes = function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/services', [ServiceController::class, 'index'])->name('services');
    Route::get('/work', PortfolioController::class)->name('work');
    Route::get('/writing', WritingController::class)->name('writing');
    Route::get('/about', AboutController::class)->name('about');
    Route::get('/contact', ContactController::class)->name('contact');

    Route::get('/intellectual-biography', fn () => redirect()->to(localized_route('about')));
    Route::get('/knowledge-library', fn () => redirect()->to(localized_route('writing')));
    Route::get('/contact-us', fn () => redirect()->to(localized_route('contact')));

    Route::post('/newsletter', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
    Route::get('/newsletter/unsubscribe', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');
    Route::get('/guide/download/{token}', [GuideController::class, 'download'])->name('guide.download');
};

Route::middleware($localizedMiddleware)->group($registerWebsiteRoutes);

foreach (array_keys(config('app.supported_locales', [])) as $locale) {
    if ($locale === $defaultLocale) {
        continue;
    }

    Route::prefix($locale)
        ->name($locale.'.')
        ->middleware($localizedMiddleware)
        ->group($registerWebsiteRoutes);
}
