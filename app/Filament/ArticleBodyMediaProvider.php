<?php

namespace App\Filament;

use App\Models\Article;
use Filament\Forms\Components\RichEditor\FileAttachmentProviders\SpatieMediaLibraryFileAttachmentProvider;

final class ArticleBodyMediaProvider extends SpatieMediaLibraryFileAttachmentProvider
{
    public function getFileAttachmentUrl(mixed $file): ?string
    {
        $media = $this->getMedia();

        if ($media === null || ! $media->has($file)) {
            return null;
        }

        $attachment = $media->get($file);

        if ($this->attribute->getFileAttachmentsVisibility() === 'public'
            && $attachment->hasGeneratedConversion(Article::BODY_IMAGE_CONVERSION)) {
            return $attachment->getUrl(Article::BODY_IMAGE_CONVERSION);
        }

        return parent::getFileAttachmentUrl($file);
    }
}
