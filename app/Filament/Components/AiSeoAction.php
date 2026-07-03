<?php

namespace App\Filament\Components;

use App\Services\SeoGenerationService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Throwable;

final class AiSeoAction
{
    public static function make(string $context = 'admin'): Action
    {
        return Action::make('generateSeo')
            ->label(__('Generate SEO with AI'))
            ->icon('heroicon-o-sparkles')
            ->color('primary')
            ->button()
            ->size('sm')
            ->extraAttributes(['class' => 'fi-ai-seo-btn'])
            ->action(function (Get $schemaGet, Set $schemaSet) use ($context): void {
                $service = new SeoGenerationService;

                if (! $service->isEnabled($context)) {
                    return;
                }

                $locales = array_keys(config('app.supported_locales', ['ar' => [], 'en' => []]));
                $sourceByLocale = [];

                foreach ($locales as $locale) {
                    $sourceByLocale[$locale] = [
                        'title' => trim((string) ($schemaGet("name.{$locale}") ?? '')),
                        'content' => trim((string) (
                            $schemaGet("content.{$locale}")
                            ?? $schemaGet("excert.{$locale}")
                            ?? ''
                        )),
                    ];
                }

                $fallbackLocale = collect($sourceByLocale)
                    ->search(fn (array $source): bool => $source['title'] !== '');

                if ($fallbackLocale === false) {
                    Notification::make()
                        ->title(__('Please fill in the title for at least one locale before generating SEO content.'))
                        ->warning()
                        ->send();

                    return;
                }

                $fallbackSource = $sourceByLocale[$fallbackLocale];

                try {
                    foreach ($locales as $locale) {
                        $source = $sourceByLocale[$locale];
                        $sourceLocale = $source['title'] !== '' ? $locale : $fallbackLocale;
                        $title = $source['title'] !== '' ? $source['title'] : $fallbackSource['title'];
                        $content = $source['content'] !== '' ? $source['content'] : $fallbackSource['content'];

                        if (trim($title) === '') {
                            continue;
                        }

                        $seo = $service->generate($title, $content, $locale, $sourceLocale);

                        $schemaSet("seo_title.{$locale}", $seo['seo_title']);
                        $schemaSet("seo_description.{$locale}", $seo['seo_description']);
                    }
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title(__('SEO Generation Failed'))
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();
                }
            });
    }
}
