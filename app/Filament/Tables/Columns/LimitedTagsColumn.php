<?php

namespace App\Filament\Tables\Columns;

use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

/**
 * Table column: tags for the current app locale (via {@see Model::tagsWithType()}),
 * showing at most {@see $maxVisible} tag badges plus a final {@code +n} badge when there are more.
 */
final class LimitedTagsColumn
{
    public static function make(
        string $name = 'tags',
        ?string $label = null,
        int $maxVisible = 3,
    ): TextColumn {
        $label ??= __('Tags');

        return TextColumn::make($name)
            ->label($label)
            ->badge()
            ->separator(',')
            ->getStateUsing(function (Model $record) use ($maxVisible): array {
                $locale = app()->getLocale();
                if (! method_exists($record, 'tagsWithType')) {
                    return [];
                }

                $names = $record->tagsWithType($locale)
                    ->map(fn ($tag) => $tag->getTranslation('name', $locale))
                    ->filter()
                    ->values()
                    ->all();

                if ($names === []) {
                    return [];
                }

                if (count($names) <= $maxVisible) {
                    return $names;
                }

                $more = count($names) - $maxVisible;

                return [
                    ...array_slice($names, 0, $maxVisible),
                    __('filament.tags_overflow', ['count' => $more]),
                ];
            })
            ->placeholder(__('-'));
    }
}
