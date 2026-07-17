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
            <a href="{{ localized_route('contact') }}#consultation" wire:navigate class="button-light" data-magnetic>
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
            <p class="footer-contact__note">{{ __('site.footer.consultation_note') }}</p>
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
                        <strong>{{ $profile['label'] }}</strong>
                        <x-phosphor-arrow-up-right class="h-3.5 w-3.5 rtl:-rotate-90" aria-hidden="true" />
                    </a>
                @endforeach
            </nav>
        </div>
    </div>
</footer>
