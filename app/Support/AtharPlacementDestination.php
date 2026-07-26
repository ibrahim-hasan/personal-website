<?php

namespace App\Support;

use App\Enums\AtharPlacement;
use App\Models\Project;
use App\Models\Service;
use Illuminate\Validation\ValidationException;

final class AtharPlacementDestination
{
    /**
     * Normalize a stable destination key while keeping About deliberately
     * unkeyed. Public destination keys are never translated slugs.
     */
    public static function validatedKey(AtharPlacement $placement, mixed $value): ?string
    {
        $key = is_string($value) ? trim($value) : '';

        if ($placement === AtharPlacement::About) {
            if ($key !== '') {
                throw ValidationException::withMessages([
                    'placement_key' => __('athar.validation.about_destination_key'),
                ]);
            }

            return null;
        }

        if ($key === '') {
            return null;
        }

        $exists = match ($placement) {
            AtharPlacement::Services => Service::query()->where('key', $key)->exists(),
            AtharPlacement::Work => Project::query()->where('key', $key)->exists(),
            AtharPlacement::About => false,
        };

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
            AtharPlacement::Services => Service::query()
                ->orderBy('order')
                ->orderBy('id')
                ->get(['id', 'key', 'name'])
                ->filter(fn (Service $service): bool => filled($service->key))
                ->mapWithKeys(fn (Service $service): array => [
                    $service->key => self::optionLabel(
                        (string) $service->getTranslation('name', $locale, false),
                        $service->key,
                    ),
                ])
                ->all(),
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
        if ($placementKey === null) {
            return __('athar.destinations.'.$placement->value.'_overview', locale: $locale);
        }

        $name = match ($placement) {
            AtharPlacement::Services => self::translatedName(
                Service::query()->where('key', $placementKey)->first(['id', 'name']),
                'name',
                $locale,
            ),
            AtharPlacement::Work => self::translatedName(
                Project::query()->where('key', $placementKey)->first(['id', 'title']),
                'title',
                $locale,
            ),
            AtharPlacement::About => null,
        };

        if (! is_string($name) || trim($name) === '') {
            return __('athar.destinations.'.$placement->value.'_overview', locale: $locale);
        }

        return match ($placement) {
            AtharPlacement::Services => __('athar.destinations.service_detail', [
                'name' => $name,
            ], $locale),
            AtharPlacement::Work => __('athar.destinations.project_detail', [
                'name' => $name,
            ], $locale),
            AtharPlacement::About => __('athar.destinations.about', locale: $locale),
        };
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
