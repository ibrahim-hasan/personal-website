<!DOCTYPE html>
<html lang="{{ current_locale() }}" dir="{{ is_rtl() ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('mail.newsletter_welcome_subject') }}</title>
</head>

<body style="margin:0;padding:0;background-color:#1d1d1e;font-family:{{ is_rtl() ? 'GhroobArabic' : 'Noto Sans' }},Arial,sans-serif;color:#fefefe;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#1d1d1e;padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:640px;background:#2c2c2d;border:1px solid #857a54;border-radius:12px;overflow:hidden;">
                    <tr>
                        <td style="padding:28px 24px;background:linear-gradient(120deg,#2c2c2d 0%,#414042 100%);text-align:center;">
                            <h1 style="margin:0;color:#e4ddc4;font-size:28px;line-height:1.4;font-weight:700;">
                                {{ __('mail.newsletter_welcome_heading') }}
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:28px 24px;">
                            <p style="margin:0 0 14px 0;color:#fefefe;font-size:16px;line-height:1.8;text-align:center">
                                {{ __('mail.newsletter_welcome_intro') }}
                            </p>

                            <p style="margin:0 0 24px 0;color:#d8d5c8;font-size:15px;line-height:1.8;text-align:center">
                                {{ __('mail.newsletter_welcome_cta_hint') }}
                            </p>

                            <p style="margin:0 0 24px 0;text-align:center;">
                                <a href="{{ localized_route('writing') }}"
                                    style="display:inline-block;padding:14px 28px;border-radius:8px;background:#e4ddc4;color:#2c2c2d;text-decoration:none;font-size:16px;font-weight:700;">
                                    {{ __('site.actions.read_notes') }}
                                </a>
                            </p>

                            <p style="margin:0 0 24px 0;color:#d8d5c8;font-size:14px;line-height:1.8;text-align:center">
                                {{ __('mail.newsletter_welcome_subscribe_note') }}
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:24px;border-top:1px solid #414042;">
                            <p style="margin:0 0 12px 0;color:#d8d5c8;font-size:13px;line-height:1.6;text-align:center">
                                {{ __('mail.newsletter_unsubscribe_note') }}
                            </p>
                            <p style="margin:0;text-align:center;">
                                <a href="{{ $unsubscribeUrl }}"
                                    style="color:#bfb9a0;font-size:12px;text-decoration:underline;">
                                    {{ __('Unsubscribe') }}
                                </a>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:16px 24px;border-top:1px solid #414042;text-align:center;color:#bfb9a0;font-size:12px;">
                            {{ __('site.brand.name') }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
