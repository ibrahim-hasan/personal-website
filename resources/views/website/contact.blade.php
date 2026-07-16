<x-layouts.front
    :title="__('site.contact.title')"
    :description="__('site.contact.description')"
    activeMenu="true">

    <section class="page-intro contact-intro">
        <div class="site-container page-intro__grid">
            <div>
                <p class="signal-label">{{ __('site.contact.eyebrow') }}</p>
                <h1 class="display-page mt-7 max-w-[12ch]">{{ __('site.contact.heading') }}</h1>
            </div>
            <p class="copy-lead max-w-[58ch] lg:self-end lg:justify-self-end">{{ $contact['availability'] }}</p>
        </div>
    </section>

    <section id="consultation" class="section-feature consultation-section">
        <div class="site-container consultation-section__grid">
            <div class="consultation-section__intro">
                <p class="signal-label signal-label--light">{{ __('site.consultation.kicker') }}</p>
                <h2 class="display-section mt-6 max-w-[12ch] text-canvas">{{ __('site.consultation.heading') }}</h2>
                <p class="copy-lead mt-7 max-w-[52ch] text-violet-100">{{ __('site.consultation.body') }}</p>

                <div class="consultation-context mt-10">
                    <p>{{ __('site.contact.context_title') }}</p>
                    <ul>
                        @foreach (__('site.contact.context_items') as $item)
                            <li><span aria-hidden="true"></span>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <livewire:website.consultation-request />
        </div>
    </section>

    <section class="section-standard bg-canvas-bright">
        <div class="site-container editorial-sidebar">
            <div class="editorial-sidebar__intro">
                <p class="signal-label">{{ __('site.contact.channels_eyebrow') }}</p>
                <h2 class="display-section mt-6 max-w-[12ch]" data-reveal>{{ __('site.contact.channels_title') }}</h2>
                <p class="copy-lead mt-7 max-w-[50ch]" data-reveal>{{ __('site.contact.channels_body') }}</p>
            </div>

            <div class="channel-list">
                @foreach ($contact['channels'] as $channel)
                    <a href="{{ $channel['href'] }}" class="channel-row" data-reveal>
                        <div>
                            <span>{{ $channel['label'] }}</span>
                            <strong>{{ $channel['value'] }}</strong>
                        </div>
                        <x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" />
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    @if ($services !== [])
        <section class="section-standard">
        <div class="site-container">
            <h2 class="display-section max-w-[14ch]" data-reveal>{{ __('site.contact.ask_title') }}</h2>
            <div class="practice-list mt-14">
                @foreach ($services as $service)
                    <a href="{{ localized_route('services') }}#{{ $service['id'] }}" class="practice-row" data-reveal>
                        <span class="practice-row__number">{{ sprintf('%02d', $loop->iteration) }}</span>
                        <h3>{{ $service['name'] }}</h3>
                        <p>{{ $service['summary'] }}</p>
                        <span class="practice-row__arrow" aria-hidden="true"><x-phosphor-arrow-up-right class="h-5 w-5 rtl:-rotate-90" /></span>
                    </a>
                @endforeach
            </div>
        </div>
        </section>
    @endif

</x-layouts.front>
