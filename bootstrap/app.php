<?php

use App\Http\Middleware\AssignApiRequestId;
use App\Http\Middleware\EnsureArticleScope;
use App\Http\Middleware\ReplayIdempotentRequest;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\SetPrivacyHeaders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Routing\Middleware\ThrottleRequestsWithRedis;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleCookieRedirect;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('horizon:snapshot')->everyFiveMinutes();
        $schedule->command('passport:purge --expired --revoked')
            ->dailyAt('03:20')
            ->environments(['production'])
            ->withoutOverlapping();
        $schedule->command('app:purge-editorial-api-audit-logs')
            ->dailyAt('03:25')
            ->environments(['production'])
            ->withoutOverlapping();
        $schedule->command('athar:expire-invitations')
            ->dailyAt('03:10')
            ->environments(['production'])
            ->withoutOverlapping();

        if (config('legal.retention.enabled')) {
            $schedule->command('privacy:purge-expired-data --force')
                ->dailyAt('03:15')
                ->environments(['production'])
                ->withoutOverlapping();
        }
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'article-scope' => EnsureArticleScope::class,
            'idempotency' => ReplayIdempotentRequest::class,
            'localize' => LaravelLocalizationRoutes::class,
            'localizationRedirect' => LaravelLocalizationRedirectFilter::class,
            'localeSessionRedirect' => LocaleSessionRedirect::class,
            'localeCookieRedirect' => LocaleCookieRedirect::class,
            'localeViewPath' => LaravelLocalizationViewPath::class,
        ]);

        $middleware->web(append: [
            SetLocale::class,
            SetPrivacyHeaders::class,
        ]);

        $middleware->api(prepend: [
            AssignApiRequestId::class,
        ]);

        $middleware->prependToPriorityList(
            AuthenticatesRequests::class,
            SetLocale::class,
        );
        $middleware->prependToPriorityList(
            ThrottleRequests::class,
            EnsureArticleScope::class,
        );
        $middleware->prependToPriorityList(
            ThrottleRequestsWithRedis::class,
            EnsureArticleScope::class,
        );

        $middleware->redirectGuestsTo(
            fn (Request $request): string => localized_route('reader.login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request): bool => $request->is('api/*'),
        );
    })->create();
