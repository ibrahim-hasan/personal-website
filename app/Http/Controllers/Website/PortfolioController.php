<?php

namespace App\Http\Controllers\Website;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Actions\Services\ServicePublicationValidator;
use App\Enums\AtharPlacement;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Services\Projects\ProjectCaseStudyPresenter;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use App\Support\AtharPublicProof;
use App\Support\PortfolioAtlas;
use App\Support\Seo\SeoMetadata;
use App\Support\SiteContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $lenses = PortfolioAtlas::lenses();
        $requestedLens = $request->query('lens');

        if ($requestedLens !== null && (! is_string($requestedLens) || trim($requestedLens) === '')) {
            return redirect()->to(localized_route('work'));
        }

        $lens = is_string($requestedLens) ? $requestedLens : null;

        if ($lens !== null && ! collect($lenses)->pluck('id')->contains($lens)) {
            abort(404);
        }

        return view('website.work', [
            'work' => array_values(array_filter(
                PortfolioAtlas::projects(),
                fn (array $project): bool => $lens === null || $project['lens'] === $lens,
            )),
            'lenses' => $lenses,
            'selectedLens' => $lens,
            'isFiltered' => $lens !== null,
            'services' => SiteContent::services(),
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::Work, app()->getLocale()),
        ]);
    }

    public function show(
        Project $project,
        ProjectCaseStudyPublicationValidator $publicationValidator,
        ProjectCaseStudyPresenter $presenter,
        ServicePublicationValidator $servicePublicationValidator,
        ArticlePublicationValidator $articlePublicationValidator,
    ): View {
        $project->loadMissing(['media', 'evidence', 'services', 'articles']);
        $eligibility = $publicationValidator->validate($project);

        abort_unless($eligibility->isEligible(), 404);

        $locale = current_locale();
        $caseStudy = $presenter->present($project, $eligibility, $locale);
        $canonicalUrl = localized_route('work.show', ['project' => $project], locale: $locale);
        $alternateUrls = collect(array_keys(supported_locales()))
            ->mapWithKeys(fn (string $alternateLocale): array => [
                $alternateLocale => localized_route('work.show', ['project' => $project], locale: $alternateLocale),
            ])
            ->all();
        $relatedServices = $caseStudy['anonymized']
            ? []
            : $project->services
                ->filter(fn (Service $service): bool => $servicePublicationValidator->isPublishable($service))
                ->map(fn (Service $service): array => [
                    'name' => (string) $service->getTranslation('name', $locale, false),
                    'url' => localized_route('services.show', ['service' => $service], locale: $locale),
                ])
                ->values()
                ->all();
        $relatedArticles = $caseStudy['anonymized']
            ? []
            : $project->articles
                ->filter(fn (Article $article): bool => $articlePublicationValidator->isPubliclyEligible($article))
                ->map(fn (Article $article): array => [
                    'key' => $article->key,
                    'title' => (string) $article->getTranslation('title', $locale, false),
                    'summary' => (string) $article->getTranslation('summary', $locale, false),
                    'type' => (string) $article->getTranslation('type', $locale, false),
                    'url' => localized_route('writing.show', ['article' => $article], locale: $locale),
                ])
                ->values()
                ->all();

        return view('website.project', [
            'caseStudy' => $caseStudy,
            'relatedServices' => $relatedServices,
            'relatedArticles' => $relatedArticles,
            'athar' => $caseStudy['anonymized']
                ? []
                : AtharPublicProof::forPlacement(AtharPlacement::Work, $locale, $project->key),
            'canonicalUrl' => $canonicalUrl,
            'alternateUrls' => $alternateUrls,
            'structuredData' => $caseStudy['anonymized'] ? null : $this->structuredData($caseStudy, $canonicalUrl, $locale),
        ]);
    }

    /**
     * @param  array{title: string, summary: string}  $caseStudy
     * @return list<array<string, mixed>>
     */
    private function structuredData(array $caseStudy, string $canonicalUrl, string $locale): array
    {
        return [
            [
                '@type' => 'CreativeWork',
                '@id' => $canonicalUrl.'#case-study',
                'name' => $caseStudy['title'],
                'description' => $caseStudy['summary'],
                'url' => $canonicalUrl,
                'inLanguage' => $locale,
                'mainEntityOfPage' => ['@id' => SeoMetadata::pageId($canonicalUrl)],
            ],
            [
                '@type' => 'BreadcrumbList',
                '@id' => $canonicalUrl.'#breadcrumb',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => __('site.nav.home'),
                        'item' => localized_route('home', locale: $locale),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => __('site.work.title'),
                        'item' => localized_route('work', locale: $locale),
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 3,
                        'name' => $caseStudy['title'],
                        'item' => $canonicalUrl,
                    ],
                ],
            ],
        ];
    }
}
