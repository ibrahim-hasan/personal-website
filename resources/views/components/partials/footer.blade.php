@php
    $year = now()->year;
    $contact = \App\Support\SiteContent::contact();
    $socialProfiles = \App\Support\SiteContent::socialProfiles();
@endphp

<footer class="site-footer" aria-label="{{ __('site.footer.label') }}">
    <section class="site-footer__cta">
        <div class="site-container grid gap-10 py-20 md:py-28 lg:grid-cols-[1fr_auto] lg:items-end">
            <h2 class="display-section max-w-4xl text-canvas">
                {{ __('site.footer.cta_title') }}
            </h2>
            <a href="{{ localized_route('contact') }}#consultation" class="button-light" data-magnetic>
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
            </a>
        </div>
    </section>

    <div class="site-container grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.2fr_0.7fr_0.7fr]">
        <div>
            <a href="{{ localized_route('home') }}" class="brand-mark brand-mark--footer">
                <span class="brand-mark__name">{{ __('site.brand.name') }}</span>
                <span class="brand-mark__signal" aria-hidden="true"></span>
            </a>
            <p class="mt-6 max-w-md text-base leading-7 text-canvas/70">
                {{ __('site.footer.body') }}
            </p>
        </div>

        <div>
            <h3 class="footer-label">{{ __('site.footer.navigate') }}</h3>
            <ul class="mt-5 flex flex-col gap-3 text-base">
                <li><a class="footer-link" href="{{ localized_route('services') }}">{{ __('site.nav.services') }}</a></li>
                <li><a class="footer-link" href="{{ localized_route('work') }}">{{ __('site.nav.work') }}</a></li>
                <li><a class="footer-link" href="{{ localized_route('writing') }}">{{ __('site.nav.writing') }}</a></li>
                <li><a class="footer-link" href="{{ localized_route('about') }}">{{ __('site.nav.about') }}</a></li>
            </ul>
        </div>

        <div>
            <h3 class="footer-label">{{ __('site.footer.contact') }}</h3>
            <ul class="mt-5 flex flex-col gap-3 text-base text-canvas/70">
                <li><a class="footer-link inline-block" href="mailto:{{ $contact['email'] }}" dir="ltr">{{ $contact['email'] }}</a></li>
                <li><a class="footer-link" href="{{ localized_route('contact') }}#consultation">{{ __('site.actions.free_consultation') }}</a></li>
            </ul>
        </div>
    </div>

    <div class="border-t border-canvas/10">
        <div class="site-container site-footer__utility py-6 text-sm text-canvas/55">
            <p>{{ __('site.footer.rights', ['year' => $year]) }}</p>

            <nav class="footer-socials" aria-label="{{ __('site.footer.social') }}">
                <span class="footer-socials__label">{{ __('site.footer.social') }}</span>
                @foreach ($socialProfiles as $profile)
                    <a
                        href="{{ $profile['href'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-socials__link"
                    >
                        <span aria-hidden="true">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <strong>{{ $profile['label'] }}</strong>
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>
