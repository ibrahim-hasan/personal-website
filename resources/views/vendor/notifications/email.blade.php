@php
    $locale = is_string($mailLocale ?? null) ? $mailLocale : app()->getLocale();
@endphp
<x-mail::message>
@if (! empty($greeting))
# {{ $greeting }}
@elseif ($level === 'error')
# {{ __('mail.error_greeting', locale: $locale) }}
@else
# {{ __('mail.default_greeting', locale: $locale) }}
@endif

@foreach ($introLines as $line)
{{ $line }}

@endforeach

@isset($actionText)
@php
    $color = match ($level) {
        'success', 'error' => $level,
        default => 'primary',
    };
@endphp
<x-mail::button :url="$actionUrl" :color="$color">
{{ $actionText }}
</x-mail::button>
@endisset

@foreach ($outroLines as $line)
{{ $line }}

@endforeach

@if (! empty($salutation))
{{ $salutation }}
@else
{{ __('mail.signoff', locale: $locale) }}<br>
{{ __('mail.brand_name', locale: $locale) }}
@endif

@isset($actionText)
<x-slot:subcopy>
{{ __('mail.action_fallback', ['action' => $actionText], $locale) }}<br>
<span class="break-all" dir="ltr">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
@endisset
</x-mail::message>
