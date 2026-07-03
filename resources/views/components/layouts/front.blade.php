@props([
    'title' => null,
    'description' => null,
    'activeMenu' => false,
    'canonicalUrl' => url()->current(),
])

@php
    $siteName = __('site.brand.name');
    $resolvedDescription = $description ?: __('site.meta.default_description');
    $resolvedTitle = $title ? "{$title} | {$siteName}" : "{$siteName} | ".__('site.meta.default_title');
@endphp

<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}" data-theme="light">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $resolvedTitle }}</title>
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <meta name="description" content="{{ $resolvedDescription }}">
    @foreach (array_keys(config('app.supported_locales', [])) as $locale)
        <link rel="alternate" hreflang="{{ $locale }}" href="{{ localized_current_url($locale) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ localized_current_url(default_locale()) }}">

    <meta property="og:type" content="website">
    <meta property="og:title" content="{{ $resolvedTitle }}">
    <meta property="og:description" content="{{ $resolvedDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ asset('images/ibrahim/ibrahim-hasan-portrait.png') }}">
    <meta property="og:site_name" content="{{ $siteName }}">

    <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
    <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="bg-stone-50 text-graphite-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-[100] focus:rounded-md focus:bg-white focus:px-4 focus:py-2 focus:text-graphite-950 focus:shadow-lg">
        {{ __('site.layout.skip') }}
    </a>

    <x-partials.navbar :activeMenu="$activeMenu" />

    <main id="main">
        {{ $slot }}
    </main>

    <x-partials.footer />

    @livewireScripts
</body>

</html>
