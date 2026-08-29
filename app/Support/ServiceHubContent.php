<?php

namespace App\Support;

use App\Models\Service;
use App\Support\Editorial\ArticleCatalog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final readonly class ServiceHubContent
{
    private const int RELATED_ARTICLE_LIMIT = 2;

    private const int RELATED_PROJECT_LIMIT = 2;

    public function __construct(private ArticleCatalog $articles) {}

    /**
     * Build the public service hub with ordered, publication-safe relationships.
     *
     * @return list<array<string, mixed>>
     */
    public function services(): array
    {
        $services = SiteContent::services();

        if (! $this->hasRelationshipTables()) {
            return $this->withoutRelationships($services);
        }

        $serviceRecords = Service::query()
            ->posted()
            ->with([
                'articles:id,key',
                'projects:id,key',
            ])
            ->get()
            ->keyBy('key');
        $publicArticles = collect($this->articles->localized(includeBody: false))->keyBy('key');
        $publicProjects = collect(PortfolioAtlas::projects())->keyBy('key');

        return collect($services)
            ->map(function (array $service) use ($serviceRecords, $publicArticles, $publicProjects): array {
                $record = $serviceRecords->get($service['key']);

                if (! $record instanceof Service) {
                    return $this->withRelationships($service, [], []);
                }

                $articles = $this->relatedItems(
                    $record->articles->pluck('key'),
                    $publicArticles,
                    self::RELATED_ARTICLE_LIMIT,
                );
                $projects = $this->relatedItems(
                    $record->projects->pluck('key'),
                    $publicProjects,
                    self::RELATED_PROJECT_LIMIT,
                )->map(function (array $project): array {
                    $project['url'] = localized_route('work').'#project-'.$project['key'];

                    return $project;
                })->values()->all();

                return $this->withRelationships($service, $articles->all(), $projects);
            })
            ->values()
            ->all();
    }

    private function hasRelationshipTables(): bool
    {
        return Schema::hasTable('services')
            && Schema::hasTable('articles')
            && Schema::hasTable('projects')
            && Schema::hasTable('article_service')
            && Schema::hasTable('project_service');
    }

    /**
     * @param  list<array<string, mixed>>  $services
     * @return list<array<string, mixed>>
     */
    private function withoutRelationships(array $services): array
    {
        return array_map(
            fn (array $service): array => $this->withRelationships($service, [], []),
            $services,
        );
    }

    /**
     * @param  array<string, mixed>  $service
     * @param  list<array<string, mixed>>  $articles
     * @param  list<array<string, mixed>>  $projects
     * @return array<string, mixed>
     */
    private function withRelationships(array $service, array $articles, array $projects): array
    {
        return [
            ...$service,
            'related_articles' => $articles,
            'related_projects' => $projects,
        ];
    }

    /**
     * @param  Collection<int, mixed>  $orderedKeys
     * @param  Collection<string, array<string, mixed>>  $publicItems
     * @return Collection<int, array<string, mixed>>
     */
    private function relatedItems(Collection $orderedKeys, Collection $publicItems, int $limit): Collection
    {
        return $orderedKeys
            ->map(fn (mixed $key): mixed => $publicItems->get((string) $key))
            ->filter(fn (mixed $item): bool => is_array($item))
            ->take($limit)
            ->values();
    }
}
