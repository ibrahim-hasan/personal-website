<?php

namespace App\Services\AgentRafeeq;

use App\Models\Project;
use App\Support\AgentRafeeqConfiguration;
use App\Support\Editorial\ArticleCatalog;
use App\Support\PortfolioAtlas;
use App\Support\SiteContent;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PublicContentExporter
{
    /** @return array<string, mixed> */
    public function manifest(): array
    {
        $sourceUrl = AgentRafeeqConfiguration::safeUrl(config('app.url'), originOnly: true);

        if ($sourceUrl === null) {
            throw new RuntimeException('The public website origin is invalid.');
        }

        foreach (['projects', 'services', 'articles'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new RuntimeException('The public content tables are unavailable.');
            }
        }

        $projectRecords = Project::query()->published()->get(['key', 'title', 'summary'])->keyBy('key');
        $originalLocale = app()->getLocale();
        $content = [];

        try {
            foreach (['ar', 'en'] as $locale) {
                app()->setLocale($locale);
                app('laravellocalization')->setLocale($locale);
                $work = [];

                foreach (PortfolioAtlas::projects() as $project) {
                    $record = $projectRecords->get($project['key'] ?? null);

                    if ($record === null || PublicContentManifest::eligibleProject($project, [
                        'title' => $record->getTranslations('title'),
                        'summary' => $record->getTranslations('summary'),
                    ], $locale) === null) {
                        continue;
                    }

                    $project['url'] = $sourceUrl.localized_route('work', locale: $locale, absolute: false).'#project-'.$project['key'];
                    $work[] = $project;
                }

                $services = array_map(function (array $service) use ($locale, $sourceUrl): array {
                    $service['url'] = $sourceUrl.localized_route('services', locale: $locale, absolute: false).'#'.$service['id'];

                    return $service;
                }, SiteContent::services());
                $content[$locale] = [
                    'work' => $work,
                    'service' => $services,
                    'article' => app(ArticleCatalog::class)->localized($locale, true),
                ];
            }
        } finally {
            app()->setLocale($originalLocale);
            app('laravellocalization')->setLocale($originalLocale);
        }

        $manifest = PublicContentManifest::build($sourceUrl, $content);

        if ($manifest['items'] === [] || count($manifest['items']) > 10000) {
            throw new RuntimeException('A sync requires between 1 and 10000 public items. Empty exports cannot withdraw all content.');
        }

        return $manifest;
    }
}
