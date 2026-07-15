<?php

namespace App\Enums;

enum ArticleNarrationStatus: string
{
    case Queued = 'queued';
    case Preparing = 'preparing';
    case Draft = 'draft';
    case Approved = 'approved';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('article_audio.narration_status.queued'),
            self::Preparing => __('article_audio.narration_status.preparing'),
            self::Draft => __('article_audio.narration_status.draft'),
            self::Approved => __('article_audio.narration_status.approved'),
            self::Failed => __('article_audio.narration_status.failed'),
        };
    }
}
