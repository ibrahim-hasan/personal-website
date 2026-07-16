<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class ReaderEmailVerificationController extends Controller
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $pathLocale = $request->segment(1);
        $locale = is_string($pathLocale) && array_key_exists($pathLocale, supported_locales())
            ? $pathLocale
            : $request->user()->preferredLocale();

        $request->fulfill();
        $request->session()->put('locale', $locale);

        return redirect()->intended(localized_route('writing', locale: $locale));
    }
}
