<?php

namespace App\Contracts\ArticleAudio;

use App\Data\ArticleAudio\NarrationDraft;

interface NarrationEditor
{
    public function prepare(string $source, string $locale): NarrationDraft;
}
