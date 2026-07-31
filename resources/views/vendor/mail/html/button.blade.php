@props([
    'url',
    'color' => 'primary',
    'align' => is_rtl() ? 'right' : 'left',
])
@php
    $backgroundColor = match ($color) {
        'success', 'green' => '#236645',
        'error', 'red' => '#a83637',
        default => '#5b2c7d',
    };
@endphp
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation" style="max-width: 100%;">
<tr>
<td align="{{ $align }}">
<a href="{{ $url }}" class="button button-{{ $color }}" target="_blank" rel="noopener" style="display: inline-block; max-width: 100%; box-sizing: border-box; border: 1px solid {{ $backgroundColor }}; border-radius: 6px; background-color: {{ $backgroundColor }}; color: #ffffff; font-size: 16px; font-weight: 700; line-height: 1.2; padding: 16px 22px; text-decoration: none; white-space: normal;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
