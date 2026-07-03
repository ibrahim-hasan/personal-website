<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = config('app.supported_locales', []);
        $availableLocales = array_keys($supportedLocales);

        $locale = null;

        $pathLocale = $request->segment(1);
        if (is_string($pathLocale) && in_array($pathLocale, $availableLocales, true)) {
            $locale = $pathLocale;
            $request->session()->put('locale', $locale);
        }

        if (! is_string($locale) && ! $this->shouldUseStoredLocale($request)) {
            $defaultLocale = app('laravellocalization')->getDefaultLocale();
            $locale = in_array($defaultLocale, $availableLocales, true)
                ? $defaultLocale
                : ($availableLocales[0] ?? 'en');

            $request->session()->put('locale', $locale);
        }

        $userLocale = $request->user()?->locale_preference;
        if (! is_string($locale) && is_string($userLocale) && in_array($userLocale, $availableLocales, true)) {
            $locale = $userLocale;
        } elseif (! is_string($locale)) {
            $sessionLocale = session('locale');
            if (is_string($sessionLocale) && in_array($sessionLocale, $availableLocales, true)) {
                $locale = $sessionLocale;
            }
        }

        if (! is_string($locale)) {
            $defaultLocale = app('laravellocalization')->getDefaultLocale();
            $locale = in_array($defaultLocale, $availableLocales, true)
                ? $defaultLocale
                : ($availableLocales[0] ?? 'en');
        }

        app('laravellocalization')->setLocale($locale);

        return $next($request);
    }

    protected function shouldUseStoredLocale(Request $request): bool
    {
        return $request->is('admin', 'admin/*', 'livewire/*', 'lang/*', 'up');
    }
}
