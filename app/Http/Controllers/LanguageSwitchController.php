<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LanguageSwitchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        $supportedLocales = config('app.supported_locales', []);

        if (! array_key_exists($locale, $supportedLocales)) {
            return redirect()->back()->with('error', __('Unsupported language.'));
        }

        if ($request->user()) {
            $request->user()->forceFill([
                'locale_preference' => $locale,
            ])->save();
        }

        $request->session()->put('locale', $locale);

        $target = localized_url($locale, url()->previous() ?: route('home'));

        return redirect()->to($target);
    }
}
