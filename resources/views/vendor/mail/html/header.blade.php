@props(['url'])

@php
    $locale = is_string($mailLocale ?? null) ? $mailLocale : app()->getLocale();
    $direction = is_rtl($locale) ? 'rtl' : 'ltr';
@endphp
<tr>
<td class="header-cell" dir="{{ $direction }}" align="center">
    <a href="{{ $url }}" class="brand-lockup" target="_blank" rel="noopener">
        <img src="{{ asset('images/brand/ibrahim-email-wordmark.png') }}" class="brand-wordmark" width="252" height="53" alt="{{ __('mail.brand_name', locale: $locale) }}">
    </a>
</td>
</tr>
