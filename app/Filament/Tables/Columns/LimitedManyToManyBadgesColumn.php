<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\ViewColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class LimitedManyToManyBadgesColumn
{
    public static function make(
        string $relationName,
        string $label,
        int $maxVisible = 2,
    ): ViewColumn {
        return ViewColumn::make($relationName)
            ->label($label)
            ->view('filament.tables.columns.limited-badges')
            ->getStateUsing(function (Model $record) use ($relationName, $maxVisible): ?array {
                $locale = app()->getLocale();

                $record->loadMissing($relationName);

                /** @var Collection<int, Model> $items */
                $items = $record->getRelation($relationName);

                $names = $items
                    ->map(function (Model $related) use ($locale): ?string {
                        if (method_exists($related, 'getTranslation')) {
                            return $related->getTranslation('name', $locale)
                                ?: $related->getTranslation('name', 'ar');
                        }

                        return $related->getAttribute('name');
                    })
                    ->filter()
                    ->values()
                    ->all();

                if ($names === []) {
                    return null;
                }

                if (count($names) <= $maxVisible) {
                    return [
                        'visible' => $names,
                        'hidden' => [],
                        'more_label' => '',
                    ];
                }

                $more = count($names) - $maxVisible;

                return [
                    'visible' => array_slice($names, 0, $maxVisible),
                    'hidden' => array_slice($names, $maxVisible),
                    'more_label' => __('filament.tags_overflow', ['count' => $more]),
                ];
            });
    }
}
