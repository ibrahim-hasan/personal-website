<?php

namespace App\Support;

use Spatie\Sluggable\Actions\GenerateSlugAction;
use Spatie\Sluggable\SlugOptions;

class GenerateLocalizedSlugAction extends GenerateSlugAction
{
    public function slugifySource(string $source, SlugOptions $options): string
    {
        $locale = preg_match('/\p{Arabic}/u', $source) === 1 ? 'ar' : $options->slugLanguage;

        return LocaleSlugger::generate($source, $locale);
    }
}
