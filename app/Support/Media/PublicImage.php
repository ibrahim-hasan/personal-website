<?php

namespace App\Support\Media;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImage;

final class PublicImage
{
    /**
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    public static function fromMedia(
        ?Media $media,
        ?string $legacySource,
        string $conversion,
        int $fallbackWidth,
        int $fallbackHeight,
    ): array {
        if (! $media instanceof Media) {
            return self::fromUrl($legacySource, $fallbackWidth, $fallbackHeight);
        }

        $hasConversion = $media->hasGeneratedConversion($conversion);
        $hasResponsiveImages = $hasConversion && $media->hasResponsiveImages($conversion);
        $responsiveImage = $hasResponsiveImages
            ? $media->responsiveImages($conversion)->files->first()
            : null;

        return [
            'src' => $hasConversion
                ? $media->getUrl($conversion)
                : ($legacySource !== null && trim($legacySource) !== '' ? self::url($legacySource) : $media->getUrl()),
            'srcset' => $hasResponsiveImages ? $media->getSrcset($conversion) : '',
            'width' => $responsiveImage instanceof ResponsiveImage ? $responsiveImage->width() : $fallbackWidth,
            'height' => $responsiveImage instanceof ResponsiveImage ? $responsiveImage->height() : $fallbackHeight,
        ];
    }

    /**
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    public static function fromUrl(?string $source, int $width, int $height): array
    {
        return [
            'src' => self::url($source),
            'srcset' => '',
            'width' => $width,
            'height' => $height,
        ];
    }

    /**
     * @return array{src: string, srcset: string, width: int, height: int}
     */
    public static function hidden(int $width, int $height): array
    {
        return [
            'src' => '',
            'srcset' => '',
            'width' => $width,
            'height' => $height,
        ];
    }

    private static function url(?string $source): string
    {
        $source = trim((string) $source);

        if ($source === '') {
            return '';
        }

        if (str_starts_with($source, '/')
            || str_starts_with($source, '//')
            || str_starts_with($source, 'http://')
            || str_starts_with($source, 'https://')
            || str_starts_with($source, 'data:')) {
            return $source;
        }

        return asset($source);
    }
}
