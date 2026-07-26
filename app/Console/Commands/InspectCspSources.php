<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

#[Signature('security:csp-inventory {--assert-enforceable : Fail when unresolved inline CSP debt exists}')]
#[Description('Inventory client-side CSP debt before considering enforcement')]
class InspectCspSources extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $inventory = [
            'inline_scripts' => [],
            'inline_styles' => [],
            'style_attributes' => [],
            'event_handlers' => [],
            'origins' => [],
        ];

        $files = $this->clientSourceFiles();

        foreach ($files as $file) {
            $contents = File::get($file);
            $path = str_replace(base_path().'/', '', $file);

            if (str_ends_with($path, '.blade.php')) {
                $this->recordCount($inventory['inline_scripts'], $path, $this->uncoveredInlineScriptCount($contents));
                $this->recordCount($inventory['inline_styles'], $path, preg_match_all('/<style\b[^>]*>/i', $contents));
                $this->recordCount($inventory['style_attributes'], $path, preg_match_all('/\bstyle\s*=/i', $contents));
                $this->recordCount($inventory['event_handlers'], $path, preg_match_all('/\son[a-z]+\s*=/i', $contents));
            }

            foreach ($this->origins($contents) as $origin) {
                $inventory['origins'][$origin] = ($inventory['origins'][$origin] ?? 0) + 1;
            }
        }

        foreach ($inventory as &$findings) {
            ksort($findings);
        }
        unset($findings);

        $this->components->info(sprintf('Scanned %d client-side source files.', count($files)));
        $this->renderFindings('Inline script tags without a CSP nonce', $inventory['inline_scripts']);
        $this->renderFindings('Inline style tags', $inventory['inline_styles']);
        $this->renderFindings('Inline style attributes', $inventory['style_attributes']);
        $this->renderFindings('Inline event handlers', $inventory['event_handlers']);

        if ($inventory['origins'] === []) {
            $this->components->info('No third-party client-side origins were found.');
        } else {
            $this->components->warn('Third-party client-side origins to review against the CSP source allowlist:');

            foreach ($inventory['origins'] as $origin => $count) {
                $this->line("  {$origin}: {$count}");
            }
        }

        $configuredOrigins = $this->configuredOrigins();

        if ($configuredOrigins !== []) {
            $this->components->info('Configured CSP third-party origins:');

            foreach ($configuredOrigins as $directive => $origins) {
                $this->line('  '.$directive.': '.implode(', ', $origins));
            }
        }

        $blockingCount = array_sum($inventory['inline_scripts'])
            + array_sum($inventory['inline_styles'])
            + array_sum($inventory['style_attributes'])
            + array_sum($inventory['event_handlers']);

        if ($blockingCount > 0) {
            $this->components->warn('CSP enforcement readiness: BLOCKED. Report-only observation remains appropriate until the listed inline code is migrated without unsafe-inline or unsafe-eval.');

            return $this->option('assert-enforceable') ? self::FAILURE : self::SUCCESS;
        }

        $this->components->info('CSP enforcement readiness: no inline-code blocker found. Third-party origins still require a manual source allowlist review.');

        return self::SUCCESS;
    }

    /**
     * @return list<string>
     */
    private function clientSourceFiles(): array
    {
        $files = [];

        foreach ([resource_path('views'), resource_path('js')] as $directory) {
            foreach (File::allFiles($directory) as $file) {
                $path = $file->getPathname();

                if (! str_ends_with($path, '.blade.php') && ! str_ends_with($path, '.js')) {
                    continue;
                }

                if (str_contains($path, '/views/emails/') || str_contains($path, '/views/vendor/mail/')) {
                    continue;
                }

                $files[] = $path;
            }
        }

        sort($files);

        return $files;
    }

    private function uncoveredInlineScriptCount(string $contents): int
    {
        preg_match_all('/<script\b[^>]*>/i', $contents, $matches);

        return count(array_filter($matches[0], function (string $tag): bool {
            return ! preg_match('/\bsrc\s*=/i', $tag)
                && ! preg_match('/\bnonce\s*=/i', $tag)
                && ! str_contains($tag, '$cspNonce');
        }));
    }

    /**
     * @return list<string>
     */
    private function origins(string $contents): array
    {
        preg_match_all('/https:\/\/([a-z0-9.-]+)/i', $contents, $matches);

        return array_map('strtolower', array_unique($matches[1]));
    }

    /**
     * @return array<string, list<string>>
     */
    private function configuredOrigins(): array
    {
        $configuredOrigins = [];
        $sources = config('security.csp.sources', []);

        if (! is_array($sources)) {
            $sources = [];
        }

        $mediaSources = $sources['media-src'] ?? [];
        $mediaOrigins = config('security.csp.media_origins', []);

        $sources['media-src'] = [
            ...(is_array($mediaSources) ? $mediaSources : []),
            ...(is_array($mediaOrigins) ? $mediaOrigins : []),
        ];

        foreach ($sources as $directive => $sourceList) {
            if (! is_string($directive) || ! is_array($sourceList)) {
                continue;
            }

            foreach ($sourceList as $source) {
                if (! is_string($source) || (parse_url($source, PHP_URL_SCHEME) ?? null) !== 'https') {
                    continue;
                }

                $host = parse_url($source, PHP_URL_HOST);

                if (! is_string($host) || $host === '') {
                    continue;
                }

                $configuredOrigins[$directive][] = strtolower($host);
            }
        }

        foreach ($configuredOrigins as &$origins) {
            $origins = array_values(array_unique($origins));
            sort($origins);
        }
        unset($origins);
        ksort($configuredOrigins);

        return $configuredOrigins;
    }

    /**
     * @param  array<string, int>  $findings
     */
    private function recordCount(array &$findings, string $path, int|false $count): void
    {
        if ($count !== false && $count > 0) {
            $findings[$path] = $count;
        }
    }

    /**
     * @param  array<string, int>  $findings
     */
    private function renderFindings(string $label, array $findings): void
    {
        if ($findings === []) {
            $this->components->info("{$label}: none.");

            return;
        }

        $this->components->warn("{$label}:");

        foreach ($findings as $path => $count) {
            $this->line("  {$path}: {$count}");
        }
    }
}
