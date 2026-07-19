@props([
    'title' => null,
    'description' => null,
    'activeMenu' => false,
    'canonicalUrl' => url()->current(),
    'alternateUrls' => [],
    'robots' => 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1',
    'ogType' => 'website',
    'ogImage' => null,
    'ogImageAlt' => null,
    'ogImageWidth' => null,
    'ogImageHeight' => null,
    'ogImageType' => null,
    'publishedAt' => null,
    'modifiedAt' => null,
    'articleAuthorUrl' => null,
    'articleSection' => null,
    'articleTags' => [],
    'schemaType' => 'WebPage',
    'structuredData' => null,
    'bodyClass' => null,
    'usesLivewire' => false,
])

@php
    $routeNameParts = explode('.', (string) request()->route()?->getName());

    if (in_array($routeNameParts[0] ?? null, array_keys(supported_locales()), true)) {
        array_shift($routeNameParts);
    }

    $baseRouteName = implode('.', $routeNameParts);
    $analyticsAllowedRoutes = [
        'home',
        'services',
        'work',
        'writing',
        'writing.show',
        'about',
        'contact',
    ];
    $allowsAnalytics = in_array($baseRouteName, $analyticsAllowedRoutes, true);
    $isSensitiveRoute = str_contains($baseRouteName, 'reader.')
        || str_contains($baseRouteName, 'verification.');
    $seo = \App\Support\Seo\SeoMetadata::fromLayout(
        title: $title,
        description: $description,
        canonicalUrl: $canonicalUrl,
        alternateUrls: $alternateUrls,
        robots: $robots,
        ogType: $ogType,
        ogImage: $ogImage,
        ogImageAlt: $ogImageAlt,
        ogImageWidth: $ogImageWidth,
        ogImageHeight: $ogImageHeight,
        ogImageType: $ogImageType,
        publishedAt: $publishedAt,
        modifiedAt: $modifiedAt,
        articleAuthorUrl: $articleAuthorUrl,
        articleSection: $articleSection,
        articleTags: $articleTags,
        schemaType: $schemaType,
        structuredData: $structuredData,
    );
@endphp

<!DOCTYPE html>
<html
    lang="{{ $seo->locale }}"
    dir="{{ is_rtl($seo->locale) ? 'rtl' : 'ltr' }}"
    data-theme="light"
    data-uses-livewire="{{ $usesLivewire ? 'true' : 'false' }}"
>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#eceae6">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="referrer" content="{{ $isSensitiveRoute ? 'no-referrer' : 'strict-origin' }}">
    <meta name="cookie-consent-version" content="{{ config('legal.cookie_consent_version') }}">

    <title>{{ $seo->title }}</title>
    <link rel="canonical" href="{{ $seo->canonicalUrl }}">
    <meta name="description" content="{{ $seo->description }}">
    <meta name="robots" content="{{ $seo->robots }}">
    @foreach ($seo->alternateUrls as $locale => $alternateUrl)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ $alternateUrl }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ $seo->xDefaultUrl }}">

    <meta property="og:type" content="{{ $seo->ogType }}">
    <meta property="og:title" content="{{ $seo->title }}">
    <meta property="og:description" content="{{ $seo->description }}">
    <meta property="og:url" content="{{ $seo->canonicalUrl }}">
    <meta property="og:image" content="{{ $seo->imageUrl }}">
    <meta property="og:image:type" content="{{ $seo->imageType }}">
    @if ($seo->imageWidth && $seo->imageHeight)
        <meta property="og:image:width" content="{{ $seo->imageWidth }}">
        <meta property="og:image:height" content="{{ $seo->imageHeight }}">
    @endif
    <meta property="og:image:alt" content="{{ $seo->imageAlt }}">
    <meta property="og:site_name" content="{{ __('site.brand.name') }}">
    <meta property="og:locale" content="{{ $seo->regionalLocale }}">
    @foreach ($seo->alternateLocales as $regionalLocale)
        <meta property="og:locale:alternate" content="{{ $regionalLocale }}">
    @endforeach
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $seo->title }}">
    <meta name="twitter:description" content="{{ $seo->description }}">
    <meta name="twitter:image" content="{{ $seo->imageUrl }}">
    <meta name="twitter:image:alt" content="{{ $seo->imageAlt }}">

    @if ($seo->ogType === 'article' && $seo->publishedAt)
        <meta property="article:published_time" content="{{ $seo->publishedAt }}">
        <meta property="article:modified_time" content="{{ $seo->modifiedAt }}">
        @if ($seo->articleAuthorUrl)
            <meta property="article:author" content="{{ $seo->articleAuthorUrl }}">
        @endif
        @if ($seo->articleSection)
            <meta property="article:section" content="{{ $seo->articleSection }}">
        @endif
        @foreach ($seo->articleTags as $articleTag)
            <meta property="article:tag" content="{{ $articleTag }}">
        @endforeach
    @endif

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @if ($allowsAnalytics && app()->isProduction() && filled(config('services.google_analytics.measurement_id')))
        <meta name="google-analytics-id" content="{{ config('services.google_analytics.measurement_id') }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($usesLivewire)
        @livewireStyles
    @endif
    @stack('head')

    <script type="application/ld+json">{!! \Illuminate\Support\Js::encode($seo->structuredData, JSON_UNESCAPED_SLASHES) !!}</script>
</head>

<body class="font-body antialiased {{ $bodyClass }}">
    <a href="#main" class="skip-link">
        {{ __('site.layout.skip') }}
    </a>

    <div class="scroll-progress" aria-hidden="true"><span></span></div>

    <x-partials.navbar :activeMenu="$activeMenu" :alternateUrls="$seo->alternateUrls" />

    <main id="main">
        {{ $slot }}
    </main>

    <x-partials.footer />

    <x-partials.cookie-consent :autoOpen="! in_array($baseRouteName, ['privacy', 'cookies', 'terms'], true)" />

    <x-partials.article-audio-player />

    <button type="button" class="back-to-top" data-back-to-top aria-label="{{ __('site.layout.back_to_top') }}" aria-hidden="true" tabindex="-1">
        <span>{{ __('site.layout.back_to_top') }}</span>
        <x-phosphor-arrow-up class="h-4 w-4" aria-hidden="true" />
    </button>

    @if ($usesLivewire)
        @livewireScripts
    @endif
    @stack('scripts')
</body>

</html>
