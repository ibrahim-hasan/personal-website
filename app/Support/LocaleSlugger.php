<?php

namespace App\Support;

use Illuminate\Support\Str;

class LocaleSlugger
{
    public static function generate(?string $value, string $locale): string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return '';
        }

        if ($locale === 'ar') {
            $slug = preg_replace('/[^\p{L}\p{N}\s-]+/u', '', $value) ?? '';
            $slug = preg_replace('/[\s_]+/u', '-', $slug) ?? '';
            $slug = preg_replace('/-+/u', '-', $slug) ?? '';

            return trim($slug, '-');
        }

        return Str::slug($value);
    }
}
