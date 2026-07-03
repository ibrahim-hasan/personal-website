<?php

namespace App\Enums;

// use Filament\Support\Contracts\HasLabel;

enum IntellectualLibraryType: string
{
    case Article = 'article';
    case Tool = 'tool';
    case Video = 'video';
    case Podcast = 'podcast';

    public function label(): string
    {
        return match ($this) {
            self::Article => __('Article'),
            self::Tool => __('Tool'),
            self::Video => __('Video'),
            self::Podcast => __('Podcast'),
        };
    }

    public function values(): array
    {
        return [
            self::Article,
            self::Tool,
            self::Video,
            self::Podcast,
        ];
    }

    public function color(): string
    {
        return match ($this) {
            self::Article => 'info',
            self::Tool => 'success',
            self::Video => 'warning',
            self::Podcast => 'danger',
        };
    }
}
