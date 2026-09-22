@props([
    'locale' => 'ar',
])

@php
    $scriptUrl = \App\Support\AgentRafeeqConfiguration::widgetUrl();
    $publicKey = config('services.agent_rafeeq_widget.public_key');
    $supportedLocale = in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
@endphp

@if ($scriptUrl !== null)
    <div
        data-agent-rafeeq-widget
        data-widget-url="{{ $scriptUrl }}"
        data-bot-key="{{ $publicKey }}"
        data-locale="{{ $supportedLocale }}"
        data-history="session"
        hidden
    ></div>
@endif
