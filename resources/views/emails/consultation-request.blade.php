<!DOCTYPE html>
<html lang="{{ $consultation['locale'] }}" dir="{{ $consultation['locale'] === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.consultation.mail_subject') }}</title>
</head>
<body style="margin:0;background:#eceae6;color:#191b22;font-family:Arial,sans-serif;">
    <div style="max-width:680px;margin:0 auto;padding:48px 24px;">
        <div style="background:#ffffff;padding:36px;border-top:6px solid #6d5eb5;">
            <p style="margin:0 0 12px;color:#6d5eb5;font-size:13px;font-weight:700;">{{ __('site.consultation.mail_kicker') }}</p>
            <h1 style="margin:0 0 28px;font-size:30px;line-height:1.2;">{{ __('site.consultation.mail_title') }}</h1>

            <dl style="margin:0;">
                <dt style="margin-top:20px;color:#65636d;font-size:12px;font-weight:700;">{{ __('site.consultation.name') }}</dt>
                <dd style="margin:6px 0 0;font-size:17px;">{{ $consultation['name'] }}</dd>

                <dt style="margin-top:20px;color:#65636d;font-size:12px;font-weight:700;">{{ __('site.consultation.email') }}</dt>
                <dd style="margin:6px 0 0;font-size:17px;"><a href="mailto:{{ $consultation['email'] }}" style="color:#493c8c;">{{ $consultation['email'] }}</a></dd>

                @if (filled($consultation['company']))
                    <dt style="margin-top:20px;color:#65636d;font-size:12px;font-weight:700;">{{ __('site.consultation.company') }}</dt>
                    <dd style="margin:6px 0 0;font-size:17px;">{{ $consultation['company'] }}</dd>
                @endif

                <dt style="margin-top:20px;color:#65636d;font-size:12px;font-weight:700;">{{ __('site.consultation.service') }}</dt>
                <dd style="margin:6px 0 0;font-size:17px;">{{ $consultation['service_label'] }}</dd>

                <dt style="margin-top:20px;color:#65636d;font-size:12px;font-weight:700;">{{ __('site.consultation.challenge') }}</dt>
                <dd style="margin:8px 0 0;white-space:pre-line;font-size:17px;line-height:1.7;">{{ $consultation['challenge'] }}</dd>
            </dl>
        </div>
    </div>
</body>
</html>
