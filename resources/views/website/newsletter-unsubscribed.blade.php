<x-layouts.front
    :title="__('site.newsletter.unsubscribed_title')"
    :description="__('site.newsletter.unsubscribed_description')"
    activeMenu="true">

    <section class="page-intro">
        <div class="site-container max-w-3xl">
            <span class="consultation-success__mark" aria-hidden="true">
                <x-phosphor-check class="h-8 w-8" />
            </span>
            <h1 class="display-page mt-8">{{ __('site.newsletter.unsubscribed_heading') }}</h1>
            <p class="copy-lead mt-6 max-w-[48ch]">{{ __('site.newsletter.unsubscribed_body') }}</p>
            <a href="{{ localized_route('home') }}" class="button-primary mt-8">
                <x-phosphor-arrow-left class="h-4 w-4 rtl:rotate-180" />
                {{ __('site.actions.return_home') }}
            </a>
        </div>
    </section>

</x-layouts.front>
