@props(['title' => null])
<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ __('athar.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="athar-shell">
    <main class="athar-frame">
        <div class="athar-topline">
            <a class="brand-mark athar-mark" href="{{ localized_route('home') }}" aria-label="{{ __('site.brand.home_aria') }}">
                <span class="brand-mark__name">{{ __('site.brand.name') }}</span>
                <span class="brand-mark__signal" aria-hidden="true"></span>
            </a>
            @if (request()->route('token'))
                @php
                    $supportedLocales = config('app.supported_locales', []);
                    $alternateLocale = collect($supportedLocales)->except(current_locale())->keys()->first();
                @endphp
                <nav class="athar-language" aria-label="{{ __('site.nav.languages') }}">
                    @if ($alternateLocale)
                        <a
                            href="{{ localized_route('athar.show', ['token' => request()->route('token')], true, $alternateLocale) }}"
                            lang="{{ $alternateLocale }}"
                            hreflang="{{ $alternateLocale }}"
                        >{{ data_get($supportedLocales, $alternateLocale.'.native') }}</a>
                    @endif
                </nav>
            @endif
        </div>
        {{ $slot }}
    </main>
</body>
</html>
