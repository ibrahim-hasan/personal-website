<x-layouts.athar :title="__('athar.email_access.title')">
    <section class="athar-panel athar-panel--center" aria-labelledby="athar-email-access-title">
        <p class="athar-kicker">{{ __('athar.email_access.eyebrow') }}</p>
        <h1 id="athar-email-access-title">{{ __('athar.email_access.title') }}</h1>
        <p class="athar-lead">{{ __('athar.email_access.body') }}</p>
        <div class="athar-trace" aria-hidden="true"><span></span><i></i><span></span></div>
        <form method="post" action="{{ $continueUrl }}" class="athar-form athar-form--wide">
            @csrf
            <button class="athar-button" type="submit">{{ __('athar.email_access.continue') }}</button>
        </form>
        <p class="athar-help">{{ __('athar.email_access.expiry') }}</p>
        <p class="athar-privacy">
            <span>{{ __('legal.privacy.athar_short_prefix') }}</span>
            <a href="{{ localized_route('privacy') }}" target="_blank" rel="noopener noreferrer">{{ __('legal.documents.privacy') }}</a>
        </p>
    </section>
</x-layouts.athar>
