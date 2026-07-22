<x-mail::message>
<p class="inquiry-kicker">{{ __('site.consultation.mail_kicker') }}</p>

# {{ __('site.consultation.mail_title') }}

<table class="inquiry-details" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td class="inquiry-label" style="{{ is_rtl($consultation['locale']) ? 'padding-left: 22px;' : 'padding-right: 22px;' }}">{{ __('site.consultation.name') }}</td>
        <td class="inquiry-value">{{ $consultation['name'] }}</td>
    </tr>
    <tr>
        <td class="inquiry-label" style="{{ is_rtl($consultation['locale']) ? 'padding-left: 22px;' : 'padding-right: 22px;' }}">{{ __('site.consultation.email') }}</td>
        <td class="inquiry-value"><a href="mailto:{{ $consultation['email'] }}" dir="ltr"><bdi>{{ $consultation['email'] }}</bdi></a></td>
    </tr>
    @if (filled($consultation['company']))
        <tr>
            <td class="inquiry-label" style="{{ is_rtl($consultation['locale']) ? 'padding-left: 22px;' : 'padding-right: 22px;' }}">{{ __('site.consultation.company') }}</td>
            <td class="inquiry-value">{{ $consultation['company'] }}</td>
        </tr>
    @endif
    <tr>
        <td class="inquiry-label" style="{{ is_rtl($consultation['locale']) ? 'padding-left: 22px;' : 'padding-right: 22px;' }}">{{ __('site.consultation.service') }}</td>
        <td class="inquiry-value">{{ $consultation['service_label'] }}</td>
    </tr>
    <tr>
        <td class="inquiry-label" style="{{ is_rtl($consultation['locale']) ? 'padding-left: 22px;' : 'padding-right: 22px;' }}">{{ __('site.consultation.challenge') }}</td>
        <td class="inquiry-value" style="white-space: pre-line;">{{ $consultation['challenge'] }}</td>
    </tr>
</table>
</x-mail::message>
