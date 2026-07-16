<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

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

        $previousUrl = url()->previous() ?: route('home');
        $target = $this->isLocalizationIgnored($previousUrl)
            ? $previousUrl
            : localized_url($locale, $previousUrl);

        return redirect()->to($target);
    }

    protected function isLocalizationIgnored(string $url): bool
    {
        $path = trim((string) parse_url($url, PHP_URL_PATH), '/');
        $ignoredPaths = array_map(
            fn (string $ignoredPath): string => trim($ignoredPath, '/'),
            config('laravellocalization.urlsIgnored', []),
        );

        return Str::is($ignoredPaths, $path);
    }
}
