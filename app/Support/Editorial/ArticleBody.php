<?php

namespace App\Support\Editorial;

use App\Models\Article;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Arr;
use Spatie\MediaLibrary\ResponsiveImages\ResponsiveImage;

final class ArticleBody
{
    private const int ARABIC_WORDS_PER_MINUTE = 175;

    private const int ENGLISH_WORDS_PER_MINUTE = 238;

    /** @var list<string> */
    private const array ALLOWED_NODE_TYPES = [
        'doc',
        'paragraph',
        'heading',
        'text',
        'blockquote',
        'bulletList',
        'orderedList',
        'listItem',
        'table',
        'tableRow',
        'tableHeader',
        'tableCell',
        'image',
        'hardBreak',
    ];

    /** @var list<string> */
    private const array ALLOWED_MARK_TYPES = ['bold', 'italic', 'link'];

    /**
     * @param  string|array<string, mixed>|null  $content
     * @return array<string, mixed>
     */
    public function toDocument(string|array|null $content): array
    {
        if (is_array($content)) {
            return $content;
        }

        if (blank($content)) {
            return [];
        }

        return RichContentRenderer::make($content)->toArray();
    }

    /**
     * Normalize API or MCP article attributes into the translatable rich body.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function normalizeInput(array $attributes): array
    {
        if (isset($attributes['body']) && is_array($attributes['body'])) {
            $attributes['body'] = [
                'ar' => $this->toDocument($attributes['body']['ar'] ?? null),
                'en' => $this->toDocument($attributes['body']['en'] ?? null),
            ];
        } elseif (isset($attributes['lead']) || isset($attributes['sections']) || isset($attributes['closing'])) {
            $attributes['body'] = [
                'ar' => $this->legacyDocumentFromInput($attributes, 'ar'),
                'en' => $this->legacyDocumentFromInput($attributes, 'en'),
            ];
        }

        unset($attributes['read_minutes']);

        return $attributes;
    }

    /**
     * Return the document that should be loaded into an article editor.
     *
     * Older articles may still store their content in the legacy lead,
     * sections, and closing fields while their rich body is empty.
     */
    public function editorDocumentForArticle(Article $article, string $locale): array
    {
        $content = $article->getAttribute(Article::bodyAttribute($locale));

        if (filled($content)) {
            return $this->toDocument($content);
        }

        return $this->toDocument($this->legacyHtml($article, $locale));
    }

    /**
     * @return array{html: string, headings: list<array{id: string, label: string}>}
     */
    public function presentForArticle(Article $article, string $locale): array
    {
        $attribute = Article::bodyAttribute($locale);
        $content = $article->getAttribute($attribute);

        if (blank($content)) {
            return $this->presentHtml($this->legacyHtml($article, $locale), $article, $locale);
        }

        return $this->presentHtml($article->renderRichContent($attribute), $article, $locale);
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     * @return array{html: string, headings: list<array{id: string, label: string}>}
     */
    public function present(string|array|null $content): array
    {
        return $this->presentHtml(RichContentRenderer::make($content)->toHtml());
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    public function text(string|array|null $content): string
    {
        if (blank($content)) {
            return '';
        }

        return trim(RichContentRenderer::make($content)->toText());
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    public function wordCount(string|array|null $content): int
    {
        $text = $this->text($content);

        if ($text === '') {
            return 0;
        }

        preg_match_all('/[\p{L}\p{N}]+(?:[’\'-][\p{L}\p{N}]+)*/u', $text, $matches);

        return count($matches[0]);
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    public function readingMinutes(string|array|null $content, string $locale): int
    {
        $wordsPerMinute = $locale === 'ar'
            ? self::ARABIC_WORDS_PER_MINUTE
            : self::ENGLISH_WORDS_PER_MINUTE;

        return max(1, (int) ceil($this->wordCount($content) / $wordsPerMinute));
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    public function isComplete(string|array|null $content): bool
    {
        if (is_array($content) && ! $this->isValidDocument($content)) {
            return false;
        }

        if ($this->wordCount($content) < 80) {
            return false;
        }

        return $this->countNodesOfType($this->toDocument($content), 'heading', level: 2) >= 1;
    }

    public function isValidDocument(mixed $content): bool
    {
        if (! is_array($content) || ($content['type'] ?? null) !== 'doc') {
            return false;
        }

        return $this->isValidNode($content, root: true);
    }

    /**
     * Remove media that is no longer referenced by the saved rich body.
     *
     * @param  list<string>  $locales
     */
    public function cleanUpUnusedImages(Article $article, array $locales = ['ar', 'en']): void
    {
        foreach ($locales as $locale) {
            $usedIds = collect($this->images($article->getTranslation('body', $locale, false)))
                ->pluck('id')
                ->filter()
                ->all();

            $article->getMedia(Article::bodyCollection($locale))
                ->reject(fn ($media): bool => in_array($media->uuid, $usedIds, strict: true))
                ->each
                ->delete();
        }
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     * @return list<array{id: string, alt: string}>
     */
    public function images(string|array|null $content): array
    {
        $images = [];
        $this->collectImages($this->toDocument($content), $images);

        return $images;
    }

    /**
     * @param  array<string, mixed>  $document
     */
    private function countNodesOfType(array $document, string $type, ?int $level = null): int
    {
        $count = 0;

        if (($document['type'] ?? null) === $type && ($level === null || Arr::get($document, 'attrs.level') === $level)) {
            $count++;
        }

        foreach (($document['content'] ?? []) as $node) {
            if (is_array($node)) {
                $count += $this->countNodesOfType($node, $type, $level);
            }
        }

        return $count;
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function isValidNode(array $node, bool $root = false): bool
    {
        $type = $node['type'] ?? null;

        if (! is_string($type) || ! in_array($type, self::ALLOWED_NODE_TYPES, strict: true)) {
            return false;
        }

        if (($root && $type !== 'doc') || (! $root && $type === 'doc')) {
            return false;
        }

        if ($type === 'text' && ! is_string($node['text'] ?? null)) {
            return false;
        }

        if ($type === 'heading' && ! in_array(Arr::get($node, 'attrs.level'), [2, 3], strict: true)) {
            return false;
        }

        if ($type === 'image' && (
            blank(Arr::get($node, 'attrs.id'))
            || blank(trim((string) Arr::get($node, 'attrs.alt')))
        )) {
            return false;
        }

        if (isset($node['marks'])) {
            if (! is_array($node['marks']) || ! array_is_list($node['marks'])) {
                return false;
            }

            foreach ($node['marks'] as $mark) {
                if (! is_array($mark) || ! in_array($mark['type'] ?? null, self::ALLOWED_MARK_TYPES, strict: true)) {
                    return false;
                }
            }
        }

        if (! isset($node['content'])) {
            return true;
        }

        if (! is_array($node['content']) || ! array_is_list($node['content'])) {
            return false;
        }

        foreach ($node['content'] as $child) {
            if (! is_array($child) || ! $this->isValidNode($child)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $node
     * @param  list<array{id: string, alt: string}>  $images
     */
    private function collectImages(array $node, array &$images): void
    {
        if (($node['type'] ?? null) === 'image') {
            $images[] = [
                'id' => (string) Arr::get($node, 'attrs.id', ''),
                'alt' => trim((string) Arr::get($node, 'attrs.alt', '')),
            ];
        }

        foreach (($node['content'] ?? []) as $child) {
            if (is_array($child)) {
                $this->collectImages($child, $images);
            }
        }
    }

    private function legacyHtml(Article $article, string $locale): string
    {
        return $this->legacyValuesToHtml(
            $article->getTranslation('lead', $locale, false),
            $article->getTranslation('sections', $locale, false),
            $article->getTranslation('closing', $locale, false),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    private function legacyDocumentFromInput(array $attributes, string $locale): array
    {
        return $this->toDocument($this->legacyValuesToHtml(
            Arr::get($attributes, "lead.{$locale}"),
            Arr::get($attributes, "sections.{$locale}"),
            Arr::get($attributes, "closing.{$locale}"),
        ));
    }

    private function legacyValuesToHtml(mixed $lead, mixed $sections, mixed $closing): string
    {
        $html = '';

        if (is_string($lead) && filled($lead)) {
            $html .= '<p>'.e($lead).'</p>';
        }

        foreach (is_array($sections) ? $sections : [] as $section) {
            if (! is_array($section)) {
                continue;
            }

            $html .= '<h2>'.e((string) ($section['heading'] ?? '')).'</h2>';

            foreach (($section['paragraphs'] ?? []) as $paragraph) {
                $html .= '<p>'.e((string) $paragraph).'</p>';
            }

            if (filled($section['points'] ?? [])) {
                $html .= '<ul>';

                foreach ($section['points'] as $point) {
                    $html .= '<li>'.e((string) $point).'</li>';
                }

                $html .= '</ul>';
            }

            if (filled($section['note'] ?? null)) {
                $html .= '<blockquote><p>'.e((string) $section['note']).'</p></blockquote>';
            }
        }

        if (is_string($closing) && filled($closing)) {
            $html .= '<p><strong>'.e($closing).'</strong></p>';
        }

        return $html;
    }

    /**
     * @return array{html: string, headings: list<array{id: string, label: string}>}
     */
    private function presentHtml(string $html, ?Article $article = null, ?string $locale = null): array
    {
        if (blank($html)) {
            return ['html' => '', 'headings' => []];
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="UTF-8"><div id="article-rich-body">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previousErrors);

        $xpath = new DOMXPath($document);
        $headings = [];

        foreach ($xpath->query('//*[@id="article-rich-body"]//h2') ?: [] as $index => $heading) {
            if (! $heading instanceof DOMElement) {
                continue;
            }

            $id = 'article-section-'.($index + 1);
            $heading->setAttribute('id', $id);
            $heading->setAttribute('class', 'article-prose__heading');
            $headings[] = [
                'id' => $id,
                'label' => trim((string) $heading->textContent),
            ];
        }

        $imageNodes = $xpath->query('//*[@id="article-rich-body"]//img');

        foreach ($imageNodes === false ? [] : iterator_to_array($imageNodes) as $image) {
            if (! $image instanceof DOMElement) {
                continue;
            }

            $image->setAttribute('loading', 'lazy');
            $image->setAttribute('decoding', 'async');

            if ($article === null || $locale === null) {
                continue;
            }

            $attachmentId = $image->getAttribute('data-id');
            $media = $article->getMedia(Article::bodyCollection($locale))
                ->firstWhere('uuid', $attachmentId);

            if ($media === null) {
                $removableNode = $image->parentNode instanceof DOMElement
                    && $image->parentNode->tagName === 'figure'
                    ? $image->parentNode
                    : $image;
                $removableNode->parentNode?->removeChild($removableNode);

                continue;
            }

            $hasOptimizedImage = $media->hasGeneratedConversion(Article::BODY_IMAGE_CONVERSION);
            $image->setAttribute(
                'src',
                $media->getUrl($hasOptimizedImage ? Article::BODY_IMAGE_CONVERSION : ''),
            );
            $srcset = $hasOptimizedImage
                ? $media->getSrcset(Article::BODY_IMAGE_CONVERSION)
                : '';

            if (filled($srcset)) {
                $image->setAttribute('srcset', $srcset);
                $image->setAttribute('sizes', '(min-width: 1024px) 720px, calc(100vw - 2rem)');
            }

            $responsiveImage = $hasOptimizedImage
                ? $media->responsiveImages(Article::BODY_IMAGE_CONVERSION)->files->first()
                : null;

            if ($responsiveImage instanceof ResponsiveImage) {
                $image->setAttribute('width', (string) $responsiveImage->width());
                $image->setAttribute('height', (string) $responsiveImage->height());
            }
        }

        foreach ($xpath->query('//*[@id="article-rich-body"]//a[@target="_blank"]') ?: [] as $link) {
            if ($link instanceof DOMElement) {
                $link->setAttribute('rel', 'noopener noreferrer');
            }
        }

        $root = $document->getElementById('article-rich-body');
        $rendered = '';

        if ($root !== null) {
            foreach ($root->childNodes as $child) {
                $rendered .= $this->saveHtml($document, $child);
            }
        }

        return ['html' => $rendered, 'headings' => $headings];
    }

    private function saveHtml(DOMDocument $document, DOMNode $node): string
    {
        return $document->saveHTML($node) ?: '';
    }
}
