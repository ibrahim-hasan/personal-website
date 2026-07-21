<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ReaderLoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReaderSessionController extends Controller
{
    public function create(Request $request): View
    {
        $this->rememberIntendedPath($request);

        return view('auth.reader-login');
    }

    public function store(ReaderLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        $reader = $request->user();
        $locale = app()->getLocale();

        if (array_key_exists($locale, supported_locales()) && $reader->locale_preference !== $locale) {
            $reader->forceFill(['locale_preference' => $locale])->save();
        }

        if (! $reader->hasVerifiedEmail()) {
            return redirect()->to(localized_route('reader.verification.notice'));
        }

        if ($reader->isReaderAccount() && ! $reader->hasAcceptedCurrentTerms()) {
            return redirect()->to(localized_route('reader.terms.acceptance'));
        }

        return redirect()->intended(localized_route('writing'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->to(localized_route('writing'));
    }

    private function rememberIntendedPath(Request $request): void
    {
        $path = $request->string('return')->toString();

        if ($path !== '' && str_starts_with($path, '/') && ! str_starts_with($path, '//')) {
            $request->session()->put('url.intended', url($path));
        }
    }
}
