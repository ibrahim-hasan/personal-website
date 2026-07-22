<x-layouts.athar :title="__('athar.unavailable.title')">
    <section class="athar-panel athar-panel--center" aria-labelledby="athar-unavailable-title">
        <p class="athar-kicker">{{ __('athar.tagline') }}</p>
        <h1 id="athar-unavailable-title">{{ __('athar.unavailable.title') }}</h1>
        <p>{{ __('athar.unavailable.body') }}</p>
        <a class="athar-button athar-button--quiet" href="{{ localized_route('contact') }}">{{ __('site.nav.contact') }}</a>
    </section>
</x-layouts.athar>
