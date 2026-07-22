@php
    $locale = is_string($mailLocale ?? null) ? $mailLocale : app()->getLocale();
    $direction = is_rtl($locale) ? 'rtl' : 'ltr';
    $alignment = $direction === 'rtl' ? 'right' : 'left';
@endphp
<tr>
<td>
<table class="footer" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation" dir="{{ $direction }}">
<tr>
<td class="footer-cell" align="{{ $alignment }}" dir="{{ $direction }}">
{{ __('mail.footer', ['year' => date('Y')], $locale) }}
</td>
</tr>
</table>
</td>
</tr>
