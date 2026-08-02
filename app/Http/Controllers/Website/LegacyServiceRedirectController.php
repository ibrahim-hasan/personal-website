<?php

namespace App\Http\Controllers\Website;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LegacyServiceRedirectController extends Controller
{
    /** @var array<string, array<string, string>> */
    private const array ALIASES = [
        'ar' => [
            'استراتيجية-التحول-الرقمي' => 'transformation',
            'استراتيجية-وتطبيق-الذكاء-الاصطناعي' => 'ai-adoption',
            'هندسة-تبني-الذكاء-الاصطناعي' => 'ai-adoption',
            'استراتيجية-البيانات-وحوكمتها' => 'data-governance',
            'حوكمة-البيانات-واستراتيجيتها' => 'data-governance',
            'الأنظمة-والأتمتة' => 'systems',
            'هندسة-الأنظمة-والأتمتة' => 'systems',
        ],
        'en' => [
            'digital-transformation-strategy' => 'transformation',
            'ai-strategy-and-implementation' => 'ai-adoption',
            'ai-adoption-engineering' => 'ai-adoption',
            'data-strategy-and-governance' => 'data-governance',
            'data-governance-strategy' => 'data-governance',
            'systems-and-automation' => 'systems',
            'systems-automation-architecture' => 'systems',
        ],
    ];

    public function __invoke(Request $request, string $legacyService): RedirectResponse
    {
        $locale = current_locale();
        $serviceKey = self::ALIASES[$locale][$legacyService] ?? null;

        abort_unless(is_string($serviceKey), 404);

        $url = localized_route('services', locale: $locale);
        $query = $request->getQueryString();

        if (is_string($query) && $query !== '') {
            $url .= '?'.$query;
        }

        return redirect()->to($url.'#service-'.$serviceKey, 301);
    }
}
