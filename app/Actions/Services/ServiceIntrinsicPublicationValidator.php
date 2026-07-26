<?php

namespace App\Actions\Services;

use App\Models\Service;
use Illuminate\Support\Arr;

/**
 * Validates only the Service's own public state and bilingual content.
 *
 * This deliberately does not traverse related Projects or Articles. It lets a
 * Project check a directly selected Service without re-entering the Service's
 * full relation gate.
 */
final class ServiceIntrinsicPublicationValidator
{
    private const array LOCALES = ['ar', 'en'];

    /** @return array<string, list<string>> */
    public function violations(Service $service, bool $requirePublicState = true): array
    {
        $violations = [];

        if ($requirePublicState) {
            if (! $service->is_active) {
                $violations['status'][] = 'The service is inactive.';
            }

            if ($service->is_draft) {
                $violations['status'][] = 'The service is still a draft.';
            }

            if ($service->trashed()) {
                $violations['status'][] = 'The service has been deleted.';
            }
        }

        if (trim((string) $service->key) === '') {
            $violations['key'][] = 'A stable service key is required.';
        }

        foreach (self::LOCALES as $locale) {
            $this->validateLocale($service, $locale, $violations);
        }

        return $violations;
    }

    public function isPublishable(Service $service): bool
    {
        return $this->violations($service) === [];
    }

    public function hasCompleteContent(Service $service): bool
    {
        return $this->violations($service, requirePublicState: false) === [];
    }

    /**
     * @param  array<string, list<string>>  $violations
     */
    private function validateLocale(Service $service, string $locale, array &$violations): void
    {
        $slug = trim((string) $service->getTranslation('slug', $locale, false));

        if ($slug === '') {
            $violations["slug.{$locale}"][] = 'A localized slug is required.';
        } elseif (Service::withTrashed()
            ->where("slug_{$locale}", $slug)
            ->where('id', '<>', $service->getKey() ?? 0)
            ->exists()) {
            $violations["slug.{$locale}"][] = 'The localized slug must be unique.';
        }

        foreach ([
            'name',
            'summary',
            'problem',
            'approach',
            'result',
            'engagement_note',
            'seo_title',
            'seo_description',
        ] as $attribute) {
            $value = trim((string) $service->getTranslation($attribute, $locale, false));

            if ($value === '') {
                $violations["{$attribute}.{$locale}"][] = 'A complete localized value is required.';

                continue;
            }

            if ($this->containsForbiddenPublicContent($value)) {
                $violations["{$attribute}.{$locale}"][] = 'The value contains a placeholder or prohibited public sales phrase.';
            }
        }

        $fitSignals = $service->getTranslation('fit_signals', $locale, false);

        if (! $this->isCompleteStringList($fitSignals, minimum: 2, maximum: 4)) {
            $violations["fit_signals.{$locale}"][] = 'Provide between two and four complete fit signals.';
        } elseif ($this->listContainsForbiddenPublicContent($fitSignals)) {
            $violations["fit_signals.{$locale}"][] = 'Fit signals contain a placeholder or prohibited public sales phrase.';
        }

        $deliverables = collect($service->deliverables ?? [])
            ->map(fn (mixed $deliverable): mixed => is_array($deliverable) ? Arr::get($deliverable, $locale) : null)
            ->all();

        if (! $this->isCompleteStringList($deliverables, minimum: 1, maximum: 5)) {
            $violations["deliverables.{$locale}"][] = 'Provide between one and five complete localized deliverables.';
        } elseif ($this->listContainsForbiddenPublicContent($deliverables)) {
            $violations["deliverables.{$locale}"][] = 'Deliverables contain a placeholder or prohibited public sales phrase.';
        }
    }

    private function isCompleteStringList(mixed $items, int $minimum, int $maximum): bool
    {
        if (! is_array($items) || count($items) < $minimum || count($items) > $maximum) {
            return false;
        }

        return collect($items)->every(
            fn (mixed $item): bool => is_string($item) && trim($item) !== '',
        );
    }

    private function listContainsForbiddenPublicContent(mixed $items): bool
    {
        return is_array($items)
            && collect($items)->contains(
                fn (mixed $item): bool => is_string($item) && $this->containsForbiddenPublicContent($item),
            );
    }

    private function containsForbiddenPublicContent(string $value): bool
    {
        return str_contains($value, 'جلسة تشخيصية')
            || preg_match('/\{\{[^}]+}}/u', $value) === 1
            || preg_match('/(?:^|\s)(?:todo|tbd|lorem ipsum|translation key|editorial note)(?:\s|$)/iu', $value) === 1
            || preg_match('/(?:^|\s)[A-Za-z0-9_.-]+::[A-Za-z0-9_.-]+(?:\s|$)/u', $value) === 1;
    }
}
