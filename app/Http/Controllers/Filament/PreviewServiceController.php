<?php

namespace App\Http\Controllers\Filament;

use App\Actions\Editorial\ArticlePublicationValidator;
use App\Enums\AtharPlacement;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Project;
use App\Models\Service;
use App\Services\Projects\ProjectCaseStudyPublicationValidator;
use App\Support\AtharPublicProof;
use App\Support\SiteContent;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Gate;

class PreviewServiceController extends Controller
{
    public function __invoke(
        Request $request,
        string $service,
        ProjectCaseStudyPublicationValidator $projectPublicationValidator,
        ArticlePublicationValidator $articlePublicationValidator,
    ): Response {
        $record = Service::withTrashed()
            ->with(['projects.evidence', 'projects.services', 'projects.articles', 'articles'])
            ->findOrFail($service);

        Gate::authorize('preview', $record);

        $locale = current_locale();
        $relatedProjects = $record->projects
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
        $relatedArticles = $record->articles
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

        return response()->view('website.service', [
            'service' => $record->toPublicArray($locale),
            'process' => SiteContent::process(),
            'relatedProjects' => $relatedProjects,
            'relatedArticles' => $relatedArticles,
            'athar' => AtharPublicProof::forPlacement(AtharPlacement::Services, $locale, $record->key),
            'canonicalUrl' => $request->url(),
            'alternateUrls' => [],
            'structuredData' => null,
        ], 200, [
            'Cache-Control' => 'private, no-store',
            'X-Robots-Tag' => 'noindex, noarchive',
        ]);
    }
}
