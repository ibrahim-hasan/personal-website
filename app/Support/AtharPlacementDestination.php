<?php

namespace App\Support;

use App\Enums\AtharPlacement;
use App\Models\Project;
use Illuminate\Validation\ValidationException;

final class AtharPlacementDestination
{
    /**
     * Normalize a stable destination key while keeping overview placements
     * deliberately unkeyed. Public destination keys are never translated slugs.
     */
    public static function validatedKey(AtharPlacement $placement, mixed $value): ?string
    {
        $key = is_string($value) ? trim($value) : '';

        if (in_array($placement, [AtharPlacement::About, AtharPlacement::Services], true)) {
            if ($key !== '') {
                throw ValidationException::withMessages([
                    'placement_key' => $placement === AtharPlacement::Services
                        ? __('athar.validation.services_overview_only')
                        : __('athar.validation.about_destination_key'),
                ]);
            }

            return null;
        }

        if ($key === '') {
            return null;
        }

        $exists = $placement === AtharPlacement::Work
            && Project::query()->where('key', $key)->exists();

        if (! $exists) {
            throw ValidationException::withMessages([
                'placement_key' => __('athar.validation.destination_not_found'),
            ]);
        }

        return $key;
    }

    /**
     * @return array<string, string>
     */
    public static function options(mixed $placement, string $locale): array
    {
        $resolvedPlacement = $placement instanceof AtharPlacement
            ? $placement
            : AtharPlacement::tryFrom((string) $placement);

        return match ($resolvedPlacement) {
            AtharPlacement::Work => Project::query()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get(['id', 'key', 'title'])
                ->filter(fn (Project $project): bool => filled($project->key))
                ->mapWithKeys(fn (Project $project): array => [
                    $project->key => self::optionLabel(
                        (string) $project->getTranslation('title', $locale, false),
                        $project->key,
                    ),
                ])
                ->all(),
            default => [],
        };
    }

    public static function label(AtharPlacement $placement, ?string $placementKey, string $locale): string
    {
        if (in_array($placement, [AtharPlacement::About, AtharPlacement::Services], true) || $placementKey === null) {
            return __('athar.destinations.'.$placement->value.'_overview', locale: $locale);
        }

        $name = self::translatedName(
            Project::query()->where('key', $placementKey)->first(['id', 'title']),
            'title',
            $locale,
        );

        if (! is_string($name) || trim($name) === '') {
            return __('athar.destinations.'.$placement->value.'_overview', locale: $locale);
        }

        return __('athar.destinations.project_detail', [
            'name' => $name,
        ], $locale);
    }

    private static function optionLabel(string $name, string $key): string
    {
        return trim($name) === '' ? $key : $name.' · '.$key;
    }

    private static function translatedName(?object $model, string $attribute, string $locale): ?string
    {
        if ($model === null || ! method_exists($model, 'getTranslation')) {
            return null;
        }

        foreach (array_unique([$locale, 'ar', 'en']) as $candidateLocale) {
            $value = $model->getTranslation($attribute, $candidateLocale, false);

            if (is_string($value) && trim($value) !== '') {
                return $value;
            }
        }

        return null;
    }
}
