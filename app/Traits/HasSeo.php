<?php

namespace App\Traits;

trait HasSeo
{
    public function getSeoTitleAttribute(): ?string
    {
        $value = $this->getAttributes()['seo_title'] ?? null;

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return $decoded[app()->getLocale()] ?? $decoded['ar'] ?? null;
        }

        return $value;
    }

    public function getSeoDescriptionAttribute(): ?string
    {
        $value = $this->getAttributes()['seo_description'] ?? null;

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return $decoded[app()->getLocale()] ?? $decoded['ar'] ?? null;
        }

        return $value;
    }
}
