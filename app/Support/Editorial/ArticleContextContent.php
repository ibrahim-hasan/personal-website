<?php

namespace App\Support\Editorial;

use App\Models\Article;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\Support\Facades\Schema;

final class ArticleContextContent
{
    /**
     * Return publication-safe service and project links assigned to an article.
     *
     * @return array{services: list<array<string, mixed>>, projects: list<array<string, mixed>>}
     */
    public function for(Article $article): array
    {
        if (! $this->hasRelationshipTables()) {
            return $this->emptyContext();
        }

        $publicServices = collect(SiteContent::services())->keyBy('key');
        $publicProjects = collect(PortfolioAtlas::projects())->keyBy('key');
        $services = $article->services()
            ->pluck('services.key')
            ->map(fn (mixed $key): mixed => $publicServices->get((string) $key))
            ->filter(fn (mixed $service): bool => is_array($service))
            ->take(2)
            ->map(function (array $service): array {
                $service['url'] = localized_route('services').'#'.$service['id'];

                return $service;
            })
            ->values()
            ->all();
        $projects = $article->projects()
            ->pluck('projects.key')
            ->map(fn (mixed $key): mixed => $publicProjects->get((string) $key))
            ->filter(fn (mixed $project): bool => is_array($project))
            ->take(2)
            ->map(function (array $project): array {
                $project['url'] = localized_route('work').'#project-'.$project['key'];

                return $project;
            })
            ->values()
            ->all();

        return [
            'services' => $services,
            'projects' => $projects,
        ];
    }

    private function hasRelationshipTables(): bool
    {
        return Schema::hasTable('services')
            && Schema::hasTable('projects')
            && Schema::hasTable('article_service')
            && Schema::hasTable('article_project');
    }

    /**
     * @return array{services: list<array<string, mixed>>, projects: list<array<string, mixed>>}
     */
    private function emptyContext(): array
    {
        return [
            'services' => [],
            'projects' => [],
        ];
    }
}
