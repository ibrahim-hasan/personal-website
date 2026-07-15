<?php

namespace App\Enums;

enum ArticleAudioStatus: string
{
    case Queued = 'queued';
    case Processing = 'processing';
    case Ready = 'ready';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Queued => __('article_audio.status.queued'),
            self::Processing => __('article_audio.status.processing'),
            self::Ready => __('article_audio.status.ready'),
            self::Failed => __('article_audio.status.failed'),
        };
    }
}
