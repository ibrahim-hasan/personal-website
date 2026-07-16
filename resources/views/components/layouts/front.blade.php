@props([
    'title' => null,
    'description' => null,
    'activeMenu' => false,
    'canonicalUrl' => url()->current(),
    'alternateUrls' => [],
    'ogType' => 'website',
    'ogImage' => null,
    'publishedAt' => null,
    'modifiedAt' => null,
    'structuredData' => null,
    'bodyClass' => null,
])

@php
    $siteName = __('site.brand.name');
    $resolvedDescription = $description ?: __('site.meta.default_description');
    $resolvedTitle = $title ? "{$title} | {$siteName}" : "{$siteName} | ".__('site.meta.default_title');
    $resolvedOgImage = $ogImage ?: asset('images/ibrahim/hero-workspace.png');
    $resolvedAlternateUrls = collect(array_keys(config('app.supported_locales', [])))
        ->mapWithKeys(fn (string $locale): array => [
            $locale => $alternateUrls[$locale] ?? localized_current_url($locale),
        ])
        ->all();
@endphp

<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#eceae6">

    <title>{{ $resolvedTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="description" content="{{ $resolvedDescription }}">
    @foreach ($resolvedAlternateUrls as $locale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $alternateUrl }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $resolvedAlternateUrls[default_locale()] }}">

    <meta property="og:type" content="{{ $ogType }}">
    <meta property="og:title" content="{{ $resolvedTitle }}">
    <meta property="og:description" content="{{ $resolvedDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $resolvedOgImage }}">
    <meta property="og:site_name" content="{{ $siteName }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', current_locale()) }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $resolvedTitle }}">
    <meta name="twitter:description" content="{{ $resolvedDescription }}">
    <meta name="twitter:image" content="{{ $resolvedOgImage }}">

    @if ($ogType === 'article' && $publishedAt)
        <meta property="article:published_time" content="{{ $publishedAt }}">
        <meta property="article:modified_time" content="{{ $modifiedAt ?: $publishedAt }}">
        <meta property="article:author" content="Ibrahim Hasan">
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')

    @if (is_array($structuredData))
        <script type="application/ld+json">@json($structuredData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)</script>
    @endif
</head>

<body class="font-body antialiased {{ $bodyClass }}">
    <a href="#main" class="skip-link">
        {{ __('site.layout.skip') }}
    </a>

    <div class="scroll-progress" aria-hidden="true"><span></span></div>

    <x-partials.navbar :activeMenu="$activeMenu" :alternateUrls="$resolvedAlternateUrls" />

    <main id="main">
        {{ $slot }}
    </main>

    <x-partials.footer />

    <x-partials.article-audio-player />

    @livewireScripts
    @stack('scripts')
</body>

</html>
