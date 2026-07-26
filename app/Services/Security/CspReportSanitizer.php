<?php

namespace App\Services\Security;

final class CspReportSanitizer
{
    /**
     * Keep the signal schema deliberately smaller than either CSP reporting
     * format. In particular, document and blocked URLs are never returned.
     *
     * @var array<string, string>
     */
    private const array DirectiveAliases = [
        'base-uri' => 'base-uri',
        'child-src' => 'frame-src',
        'connect-src' => 'connect-src',
        'default-src' => 'default-src',
        'font-src' => 'font-src',
        'form-action' => 'form-action',
        'frame-ancestors' => 'frame-ancestors',
        'frame-src' => 'frame-src',
        'img-src' => 'img-src',
        'manifest-src' => 'connect-src',
        'media-src' => 'media-src',
        'object-src' => 'object-src',
        'script-src' => 'script-src',
        'script-src-attr' => 'script-src',
        'script-src-elem' => 'script-src',
        'style-src' => 'style-src',
        'style-src-attr' => 'style-src',
        'style-src-elem' => 'style-src',
        'worker-src' => 'connect-src',
    ];

    private const int MaximumReportsPerRequest = 10;

    /**
     * @param  array<mixed>  $payload
     * @return list<array{directive: string, category: string}>
     */
    public function signals(array $payload): array
    {
        $signals = [];

        foreach ($this->reportBodies($payload) as $body) {
            $directive = $this->directive($body);

            if ($directive === null) {
                continue;
            }

            $signal = [
                'directive' => $directive,
                'category' => $this->category($directive),
            ];

            if (! in_array($signal, $signals, true)) {
                $signals[] = $signal;
            }

            if (count($signals) >= self::MaximumReportsPerRequest) {
                break;
            }
        }

        return $signals;
    }

    /**
     * @param  array<mixed>  $payload
     * @return list<array<mixed>>
     */
    private function reportBodies(array $payload): array
    {
        $legacyReport = $payload['csp-report'] ?? null;

        if (is_array($legacyReport)) {
            return [$legacyReport];
        }

        if (isset($payload['type'])) {
            return $this->reportingApiBody($payload);
        }

        if (! array_is_list($payload)) {
            return $this->looksLikeCspBody($payload) ? [$payload] : [];
        }

        $bodies = [];

        foreach ($payload as $report) {
            if (! is_array($report)) {
                continue;
            }

            foreach ($this->reportingApiBody($report) as $body) {
                $bodies[] = $body;
            }
        }

        return $bodies;
    }

    /**
     * @param  array<mixed>  $report
     * @return list<array<mixed>>
     */
    private function reportingApiBody(array $report): array
    {
        if (($report['type'] ?? null) !== 'csp-violation' || ! is_array($report['body'] ?? null)) {
            return [];
        }

        return [$report['body']];
    }

    /**
     * @param  array<mixed>  $body
     */
    private function directive(array $body): ?string
    {
        foreach (['effectiveDirective', 'effective-directive', 'violatedDirective', 'violated-directive'] as $key) {
            $value = $body[$key] ?? null;

            if (! is_string($value) || $value === '') {
                continue;
            }

            $candidate = strtok(trim($value), " \t\r\n");

            if (! is_string($candidate)) {
                return 'other';
            }

            $candidate = strtolower($candidate);

            if (! preg_match('/^[a-z][a-z0-9-]*$/', $candidate)) {
                return 'other';
            }

            return self::DirectiveAliases[$candidate] ?? 'other';
        }

        return null;
    }

    private function category(string $directive): string
    {
        return match ($directive) {
            'script-src' => 'script',
            'style-src' => 'style',
            'font-src', 'img-src', 'media-src', 'connect-src' => 'network',
            'base-uri', 'default-src', 'form-action', 'frame-ancestors', 'frame-src', 'object-src' => 'document',
            default => 'other',
        };
    }

    /**
     * @param  array<mixed>  $body
     */
    private function looksLikeCspBody(array $body): bool
    {
        return array_key_exists('effectiveDirective', $body)
            || array_key_exists('effective-directive', $body)
            || array_key_exists('violatedDirective', $body)
            || array_key_exists('violated-directive', $body);
    }
}
