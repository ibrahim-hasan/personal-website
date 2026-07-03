<?php

namespace App\Filament\Components;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Tabs;
use Illuminate\Database\Eloquent\Model;

class TranslatableInfolistTabs
{
    /**
     * @param  array<string, array{
     *     label?: string,
     *     relation?: string,
     *     html?: bool,
     *     columnSpanFull?: bool,
     *     badge?: bool,
     *     formatStateUsing?: (callable(mixed): string)|null,
     * }>  $fields
     */
    public static function make(array $fields, int $columns = 2): Tabs
    {
        $locales = config('translatable.locales', ['ar', 'en']);
        $tabsSchema = [];

        foreach ($locales as $locale) {
            $entries = [];

            foreach ($fields as $field => $options) {
                $entry = TextEntry::make("{$field}.{$locale}")
                    ->label($options['label'] ?? __('admin.fields.'.$field))
                    ->getStateUsing(function (Model $record) use ($field, $locale, $options): ?string {
                        $target = $record;

                        if (isset($options['relation'])) {
                            $target = $record->{$options['relation']};

                            if ($target === null) {
                                return null;
                            }
                        }

                        if (! method_exists($target, 'getTranslation')) {
                            return null;
                        }

                        return $target->getTranslation($field, $locale);
                    });

                if ($options['html'] ?? false) {
                    $entry->html();
                }

                if ($options['columnSpanFull'] ?? false) {
                    $entry->columnSpanFull();
                }

                if ($options['badge'] ?? false) {
                    $entry->badge();
                }

                if (isset($options['formatStateUsing']) && is_callable($options['formatStateUsing'])) {
                    $entry->formatStateUsing($options['formatStateUsing']);
                }

                $entries[] = $entry;
            }

            $tabsSchema[$locale] = $entries;
        }

        return TranslatableTabs::make($tabsSchema, $columns);
    }
}
