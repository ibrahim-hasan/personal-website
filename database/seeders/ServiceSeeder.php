<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Support\LocaleSlugger;
use App\Support\SiteContent;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (SiteContent::serviceDefaults() as $index => $service) {
            if (Service::withTrashed()->where('key', $service['id'])->exists()) {
                continue;
            }

            Service::query()->create([
                'key' => $service['id'],
                'slug' => $this->localizedSlugs($service['name'], $service['id']),
                'name' => $service['name'],
                'summary' => $service['summary'],
                'problem' => $service['problem'],
                'approach' => $service['approach'],
                'deliverables' => $service['deliverables'],
                'result' => $service['result'],
                'order' => $index + 1,
                'is_draft' => false,
                'is_active' => true,
            ]);
        }
    }

    /**
     * @param  array<string, string>  $translations
     * @return array<string, string>
     */
    private function localizedSlugs(array $translations, string $fallback): array
    {
        return collect(config('translatable.locales', ['ar', 'en']))
            ->mapWithKeys(function (string $locale) use ($translations, $fallback): array {
                $slug = LocaleSlugger::generate($translations[$locale] ?? $fallback, $locale);

                return [$locale => $slug !== '' ? $slug : $fallback];
            })
            ->all();
    }
}
