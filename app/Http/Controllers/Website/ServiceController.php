<?php

namespace App\Http\Controllers\Website;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Actions\Services\ServicePublicationValidator;
use App\Enums\AtharPlacement;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use App\Support\AtharPublicProof;
use App\Support\Seo\SeoMetadata;
use App\Support\SiteContent;
use Illuminate\View\View;

class ServiceController extends Controller
{
    public function index(): View
    {
        return view('website.services', [
            'services' => SiteContent::services(),
            'process' => SiteContent::process(),
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::Services, app()->getLocale()),
        ]);
    }

    public function show(
        Service $service,
        ServicePublicationValidator $validator,
        ProjectCaseStudyPublicationValidator $projectPublicationValidator,
        ArticlePublicationValidator $articlePublicationValidator,
    ): View {
        $service->loadMissing(['projects.evidence', 'projects.services', 'projects.articles', 'articles']);

        abort_unless($validator->isPublishable($service), 404);

        $locale = current_locale();
        $publicService = $service->toPublicArray($locale);
        $canonicalUrl = localized_route('services.show', ['service' => $service], locale: $locale);
        $alternateUrls = collect(array_keys(supported_locales()))
            ->mapWithKeys(fn (string $alternateLocale): array => [
                $alternateLocale => localized_route('services.show', ['service' => $service], locale: $alternateLocale),
            ])
            ->all();
        $relatedProjects = $service->projects
            ->map(function (Project $project) use ($locale, $projectPublicationValidator): ?array {
                if ($project->isAnonymizedForPublic()
                    || ! $projectPublicationValidator->isEligibleForPublicRelation($project)) {
                    return null;
                }

                return [
                    'key' => $project->key,
                    'title' => (string) $project->getTranslation('title', $locale, false),
                    'summary' => (string) $project->getTranslation('summary', $locale, false),
                    'url' => localized_route('work.show', ['project' => $project], locale: $locale),
                ];
            })
            ->filter()
            ->values()
            ->all();
        $relatedArticles = $service->articles
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

        return view('website.service', [
            'service' => $publicService,
            'process' => SiteContent::process(),
            'relatedProjects' => $relatedProjects,
            'relatedArticles' => $relatedArticles,
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::Services, $locale, $service->key),
            'canonicalUrl' => $canonicalUrl,
            'alternateUrls' => $alternateUrls,
            'structuredData' => [
                [
                    '@type' => 'Service',
                    '@id' => $canonicalUrl.'#service',
                    'name' => $publicService['name'],
                    'description' => $publicService['seo_description'],
                    'url' => $canonicalUrl,
                    'inLanguage' => $locale,
                    'provider' => ['@id' => SeoMetadata::personId()],
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
                            'name' => __('site.services.title'),
                            'item' => localized_route('services', locale: $locale),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 3,
                            'name' => $publicService['name'],
                            'item' => $canonicalUrl,
                        ],
                    ],
                ],
            ],
        ]);
    }
}
