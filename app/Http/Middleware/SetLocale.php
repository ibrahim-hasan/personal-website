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

        $locale = $this->livewireSnapshotLocale($request, $availableLocales);

        $pathLocale = $request->segment(1);
        if (! is_string($locale) && is_string($pathLocale) && in_array($pathLocale, $availableLocales, true)) {
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
        return $request->is('admin', 'admin/*', 'livewire/*', 'livewire-*', 'livewire-*/*', 'lang/*', 'up');
    }

    /**
     * Resolve the original page locale from a Livewire update snapshot.
     *
     * Livewire posts to a locale-free endpoint, so a reader's stored preference
     * may differ from the page that initiated the update. This must run before
     * route model binding rehydrates the original page route.
     *
     * @param  array<int, string>  $availableLocales
     */
    protected function livewireSnapshotLocale(Request $request, array $availableLocales): ?string
    {
        if (! $request->is('livewire/*')) {
            return null;
        }

        $snapshot = $request->input('components.0.snapshot');

        if (! is_string($snapshot)) {
            return null;
        }

        $payload = json_decode($snapshot, true);
        $path = is_array($payload) ? ($payload['memo']['path'] ?? null) : null;

        if (! is_string($path)) {
            return null;
        }

        $locale = explode('/', trim($path, '/'))[0] ?? null;

        return is_string($locale) && in_array($locale, $availableLocales, true)
            ? $locale
            : null;
    }
}
