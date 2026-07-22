@php
    $locale = is_string($mailLocale ?? null) ? $mailLocale : app()->getLocale();
    $direction = is_rtl($locale) ? 'rtl' : 'ltr';
    $alignment = $direction === 'rtl' ? 'right' : 'left';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
<head>
    <title>{{ config('app.name') }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="color-scheme" content="light">
    <meta name="supported-color-schemes" content="light">
    <style>
        @font-face {
            font-family: 'Thmanyah Sans';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('fonts/thmanyah/thmanyah-sans-regular.woff2') }}') format('woff2');
        }

        @font-face {
            font-family: 'Thmanyah Sans';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('fonts/thmanyah/thmanyah-sans-bold.woff2') }}') format('woff2');
        }

        @font-face {
            font-family: 'Thmanyah Text';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('fonts/thmanyah/thmanyah-text-regular.woff2') }}') format('woff2');
        }

        @font-face {
            font-family: 'Thmanyah Text';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('fonts/thmanyah/thmanyah-text-bold.woff2') }}') format('woff2');
        }

        @font-face {
            font-family: 'Noto Sans';
            font-style: normal;
            font-weight: 400;
            font-display: swap;
            src: url('{{ asset('fonts/noto-sans/NotoSans-400.woff2') }}') format('woff2');
        }

        @font-face {
            font-family: 'Noto Sans';
            font-style: normal;
            font-weight: 700;
            font-display: swap;
            src: url('{{ asset('fonts/noto-sans/NotoSans-700.woff2') }}') format('woff2');
        }

        @media only screen and (max-width: 600px) {
            .inner-body,
            .footer {
                width: 100% !important;
            }

            .content-cell {
                padding: 28px 22px !important;
            }

            .header-cell {
                padding: 24px 22px !important;
            }
        }

        @media only screen and (max-width: 480px) {
            .button {
                display: block !important;
                width: 100% !important;
            }
        }
    </style>
    {!! $head ?? '' !!}
</head>
<body dir="{{ $direction }}" style="margin: 0; padding: 0;" bgcolor="#f3f1f6">
<table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation" dir="{{ $direction }}">
<tr>
<td align="center">
<table class="content" width="100%" cellpadding="0" cellspacing="0" role="presentation" dir="{{ $direction }}">
{!! $header ?? '' !!}

<tr>
<td class="body" width="100%" dir="{{ $direction }}" style="border: hidden !important;">
<table class="inner-body" align="center" width="600" cellpadding="0" cellspacing="0" role="presentation" dir="{{ $direction }}">
<tr>
<td class="content-cell" dir="{{ $direction }}" align="{{ $alignment }}" style="text-align: {{ $alignment }};">
{!! Illuminate\Mail\Markdown::parse($slot) !!}

{!! $subcopy ?? '' !!}
</td>
</tr>
</table>
</td>
</tr>

{!! $footer ?? '' !!}
</table>
</td>
</tr>
</table>
</body>
</html>
