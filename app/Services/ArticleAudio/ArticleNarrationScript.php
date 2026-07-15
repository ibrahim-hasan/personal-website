<?php

namespace App\Services\ArticleAudio;

use App\Support\Editorial\Article;

class ArticleNarrationScript
{
    public function build(Article $article, string $locale): string
    {
        return $this->fromLocalized($article->localized($locale), $locale);
    }

    /**
     * @param  array<string, mixed>  $article
     */
    public function fromLocalized(array $article, string $locale): string
    {
        $segments = [];
        $segments[] = $this->sentence((string) ($article['title'] ?? ''));
        $segments[] = $this->sentence((string) ($article['lead'] ?? ''));

        foreach (($article['sections'] ?? []) as $section) {
            if (! is_array($section)) {
                continue;
            }

            $segments[] = $this->sentence((string) ($section['heading'] ?? ''));

            foreach (($section['paragraphs'] ?? []) as $paragraph) {
                $segments[] = $this->sentence((string) $paragraph);
            }

            foreach (($section['points'] ?? []) as $point) {
                $segments[] = $this->sentence((string) $point);
            }

            if (filled($section['note'] ?? null)) {
                $prefix = $locale === 'ar' ? 'ملاحظة.' : 'Note.';
                $segments[] = $prefix.' '.$this->sentence((string) $section['note']);
            }
        }

        $segments[] = $this->sentence((string) ($article['closing'] ?? ''));

        return implode("\n\n", array_values(array_filter($segments)));
    }

    public function fingerprint(Article $article, string $locale): string
    {
        return hash('sha256', $this->build($article, $locale));
    }

    private function sentence(string $value): string
    {
        $clean = preg_replace('/<(script|style)\b[^>]*>.*?<\/\1>/isu', ' ', $value) ?? $value;
        $clean = strip_tags($clean);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = str_replace(["\u{00A0}", "\u{200B}", "\u{200C}", "\u{200D}", "\u{FEFF}"], ' ', $clean);
        $clean = preg_replace('/[ \t]+/u', ' ', $clean) ?? $clean;
        $clean = preg_replace('/\s*\R\s*/u', ' ', $clean) ?? $clean;
        $clean = trim($clean);

        if ($clean === '') {
            return '';
        }

        return preg_match('/[.!?؟؛:،]$/u', $clean) === 1 ? $clean : $clean.'.';
    }
}
