@php
    $year = now()->year;
    $contact = \App\Support\SiteContent::contact();
    $socialProfiles = \App\Support\SiteContent::socialProfiles();
@endphp

<footer class="site-footer" aria-label="{{ __('site.footer.label') }}">
    <section class="site-footer__cta">
        <div class="site-container site-footer__cta-layout">
            <h2 class="display-section max-w-4xl text-canvas">
                {{ __('site.footer.cta_title') }}
            </h2>
            <a href="{{ localized_route('contact') }}#consultation" wire:navigate class="button-light site-footer__cta-action" data-magnetic>
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
            </a>
        </div>
    </section>

    <div class="site-container grid gap-12 py-16 md:grid-cols-2 lg:grid-cols-[1.1fr_0.65fr_1fr]">
        <div>
            <a href="{{ localized_route('home') }}" wire:navigate class="brand-mark brand-mark--footer">
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
                <li><a class="footer-link" wire:navigate href="{{ localized_route('services') }}">{{ __('site.nav.services') }}</a></li>
                <li><a class="footer-link" wire:navigate href="{{ localized_route('work') }}">{{ __('site.nav.work') }}</a></li>
                <li><a class="footer-link" wire:navigate href="{{ localized_route('writing') }}">{{ __('site.nav.writing') }}</a></li>
                <li><a class="footer-link" wire:navigate href="{{ localized_route('about') }}">{{ __('site.nav.about') }}</a></li>
                <li><a class="footer-link" wire:navigate href="{{ localized_route('contact') }}">{{ __('site.nav.contact') }}</a></li>
            </ul>
        </div>

        <div class="footer-contact">
            <h3 class="footer-label">{{ __('site.footer.contact') }}</h3>
            <p class="footer-contact__intro">{{ __('site.footer.contact_intro') }}</p>
            <a class="footer-contact__email" href="mailto:{{ $contact['email'] }}">
                <span>{{ __('site.footer.email_label') }}</span>
                <strong dir="ltr">{{ $contact['email'] }}</strong>
            </a>
            <a class="footer-contact__action" wire:navigate href="{{ localized_route('contact') }}#consultation">
                <span>{{ __('site.actions.free_consultation') }}</span>
                <x-phosphor-arrow-up-right class="h-4 w-4 rtl:-rotate-90" aria-hidden="true" />
            </a>
        </div>
    </div>

    <div class="border-t border-canvas/10">
        <div class="site-container site-footer__utility py-6 text-sm text-canvas/55" data-back-to-top-safe-zone>
            <p class="site-footer__rights">
                <span>{{ __('site.footer.rights_copy') }}</span>
                <span dir="ltr">© {{ $year }}</span>
            </p>

            <nav class="flex flex-wrap justify-center gap-x-4 gap-y-2 text-sm" aria-label="{{ __('site.footer.legal') }}">
                <a class="footer-link" data-no-navigate href="{{ localized_route('privacy') }}">{{ __('site.footer.privacy_policy') }}</a>
                <a class="footer-link" data-no-navigate href="{{ localized_route('cookies') }}">{{ __('site.footer.cookie_policy') }}</a>
                <a class="footer-link" data-no-navigate href="{{ localized_route('terms') }}">{{ __('site.footer.terms_of_use') }}</a>
                <button type="button" class="footer-link" data-open-cookie-preferences>{{ __('site.footer.cookie_preferences') }}</button>
            </nav>

            <nav class="footer-socials" aria-label="{{ __('site.footer.social') }}">
                <span class="footer-socials__label">{{ __('site.footer.social') }}</span>
                <span class="footer-socials__links">
                    @foreach ($socialProfiles as $profile)
                        <a
                            href="{{ $profile['href'] }}"
                            target="_blank"
                            rel="noopener noreferrer"
                            class="footer-socials__link"
                            aria-label="{{ $profile['label'] }}"
                            title="{{ $profile['label'] }}"
                        >
                            @switch($profile['platform'])
                                @case('linkedin')
                                    <x-phosphor-linkedin-logo class="size-5" aria-hidden="true" />
                                    @break
                                @case('facebook')
                                    <x-phosphor-facebook-logo class="size-5" aria-hidden="true" />
                                    @break
                                @case('twitter')
                                    <x-phosphor-x-logo class="size-5" aria-hidden="true" />
                                    @break
                                @case('instagram')
                                    <x-phosphor-instagram-logo class="size-5" aria-hidden="true" />
                                    @break
                                @case('youtube')
                                    <x-phosphor-youtube-logo class="size-5" aria-hidden="true" />
                                    @break
                            @endswitch
                            <span class="sr-only">{{ $profile['label'] }}</span>
                        </a>
                    @endforeach
                </span>
            </nav>
        </div>
    </div>
</footer>
