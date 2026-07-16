<?php

namespace App\Filament\Components;

use App\Models\Article;
use App\Services\SeoGenerationService;
use App\Support\Ai\ArticleSeoSource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

final class AiSeoAction
{
    private const MAX_ATTEMPTS_PER_HOUR = 4;

    public static function make(): Action
    {
        return Action::make('generateSeo')
            ->label(__('Generate SEO with AI'))
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->button()
            ->size('sm')
            ->extraAttributes(['class' => 'fi-ai-seo-btn'])
            ->requiresConfirmation()
            ->modalHeading(__('Generate bilingual SEO metadata'))
            ->modalDescription(__('One AI request will draft Arabic and English metadata from the article. Review the result before saving.'))
            ->modalSubmitActionLabel(__('Generate metadata'))
            ->authorize(fn (?Article $record): bool => self::canManage($record))
            ->visible(fn (?Article $record, SeoGenerationService $service): bool => filled(config('ai.providers.openai.key'))
                && $service->isEnabled()
                && self::canManage($record))
            ->action(function (
                Get $schemaGet,
                Set $schemaSet,
                SeoGenerationService $service,
                ArticleSeoSource $sourceBuilder,
                ?Article $record,
            ): void {
                self::authorize($record);

                $userId = (int) auth()->id();
                $rateLimitKey = "ai-seo-generation:{$userId}";

                if (RateLimiter::tooManyAttempts($rateLimitKey, self::MAX_ATTEMPTS_PER_HOUR)) {
                    Notification::make()
                        ->title(__('AI SEO limit reached'))
                        ->body(__('Try again in :seconds seconds.', [
                            'seconds' => RateLimiter::availableIn($rateLimitKey),
                        ]))
                        ->warning()
                        ->send();

                    return;
                }

                $locales = array_values(array_intersect(
                    config('translatable.locales', ['ar', 'en']),
                    ['ar', 'en'],
                ));
                $sources = [];

                foreach ($locales as $locale) {
                    $sources[$locale] = [
                        'title' => trim((string) ($schemaGet("title.{$locale}") ?? '')),
                        'content' => $sourceBuilder->fromState([
                            'summary' => $schemaGet("summary.{$locale}"),
                            'lead' => $schemaGet("lead.{$locale}"),
                            'sections' => $schemaGet("sections.{$locale}"),
                            'closing' => $schemaGet("closing.{$locale}"),
                        ]),
                        'source_locale' => $locale,
                    ];
                }

                $fallbackLocale = collect($sources)
                    ->search(fn (array $source): bool => $source['title'] !== '');

                if ($fallbackLocale === false) {
                    Notification::make()
                        ->title(__('Please fill in the title for at least one locale before generating SEO content.'))
                        ->warning()
                        ->send();

                    return;
                }

                foreach ($sources as $locale => &$source) {
                    if ($source['title'] === '') {
                        $source['source_locale'] = $fallbackLocale;
                    }
                }
                unset($source);

                RateLimiter::hit($rateLimitKey, 3600);

                try {
                    foreach ($service->generateForLocales($sources) as $locale => $seo) {
                        $schemaSet("seo_title.{$locale}", $seo['seo_title']);
                        $schemaSet("seo_description.{$locale}", $seo['seo_description']);
                    }

                    Notification::make()
                        ->title(__('SEO metadata generated for Arabic and English.'))
                        ->success()
                        ->send();
                } catch (Throwable $exception) {
                    report($exception);

                    Notification::make()
                        ->title(__('SEO Generation Failed'))
                        ->body(__('Verify the server AI configuration and try again.'))
                        ->danger()
                        ->send();
                }
            });
    }

    private static function canManage(?Article $record): bool
    {
        return $record instanceof Article
            ? Gate::allows('update', $record)
            : Gate::allows('create', Article::class);
    }

    private static function authorize(?Article $record): void
    {
        Gate::authorize(
            $record instanceof Article ? 'update' : 'create',
            $record instanceof Article ? $record : Article::class,
        );
    }
}
