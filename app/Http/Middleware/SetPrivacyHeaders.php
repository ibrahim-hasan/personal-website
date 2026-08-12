<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class SetPrivacyHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->cspReportingIsEnabled()) {
            Vite::useCspNonce();
        }

        $response = $next($request);
        $this->setSecurityHeaders($response);

        $routeName = (string) $request->route()?->getName();
        $isWebsiteMetricsRoute = $routeName === 'api.v1.metrics.website';
        $isSensitiveRoute = str_contains($routeName, 'reader.')
            || str_contains($routeName, 'verification.')
            || str_contains($routeName, 'athar.')
            || $routeName === 'security.csp-reports'
            || $isWebsiteMetricsRoute;

        $response->headers->set(
            'Referrer-Policy',
            $isSensitiveRoute ? 'no-referrer' : 'strict-origin',
        );

        if ($isWebsiteMetricsRoute) {
            $response->headers->set('Cache-Control', 'private, no-store');
            $response->headers->set('Pragma', 'no-cache');
            $response->headers->set('X-Robots-Tag', 'noindex, noarchive');
        } elseif ($isSensitiveRoute) {
            $response->headers->set('Cache-Control', 'no-store, no-cache, max-age=0, must-revalidate');
            $response->headers->set('Pragma', 'no-cache');
        }

        return $response;
    }

    private function setSecurityHeaders(Response $response): void
    {
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', (string) config('security.permissions_policy'));

        if (! $this->isHtmlResponse($response)) {
            return;
        }

        $reporting = $this->reportingConfiguration();
        $policy = $this->reportOnlyContentSecurityPolicy($reporting);

        if ($policy !== null) {
            $response->headers->set('Content-Security-Policy-Report-Only', $policy);
        }

        if ($reporting !== null) {
            $response->headers->set('Reporting-Endpoints', sprintf('%s="%s"', $reporting['group'], $reporting['endpoint']));
            $response->headers->set('Report-To', json_encode([
                'group' => $reporting['group'],
                'max_age' => $reporting['max_age'],
                'endpoints' => [['url' => $reporting['endpoint']]],
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        }
    }

    private function isHtmlResponse(Response $response): bool
    {
        return $response->getContent() !== ''
            && str_starts_with((string) $response->headers->get('Content-Type'), 'text/html');
    }

    /**
     * @param  array{group: string, endpoint: string, max_age: int}|null  $reporting
     */
    private function reportOnlyContentSecurityPolicy(?array $reporting): ?string
    {
        if (! $this->cspReportingIsEnabled()) {
            return null;
        }

        $directives = [
            'default-src' => ["'self'"],
            'object-src' => ["'none'"],
            'base-uri' => ["'self'"],
            'form-action' => ["'self'"],
            'frame-ancestors' => ["'none'"],
        ];

        foreach (['script-src', 'style-src', 'font-src', 'img-src', 'media-src', 'connect-src', 'frame-src'] as $directive) {
            $sources = $this->trustedSources(
                $directive,
                config("security.csp.sources.$directive", []),
            );

            if ($sources !== []) {
                $directives[$directive] = $sources;
            }
        }

        $nonce = Vite::cspNonce();

        if ($nonce !== null) {
            $directives['script-src'] = array_values(array_unique([
                ...($directives['script-src'] ?? ["'self'"]),
                "'nonce-{$nonce}'",
            ]));
        }

        $mediaOrigins = $this->trustedSources('media-src', config('security.csp.media_origins', []));

        foreach (['img-src', 'media-src'] as $directive) {
            $directives[$directive] = array_values(array_unique([
                ...($directives[$directive] ?? ["'self'"]),
                ...$mediaOrigins,
            ]));
        }

        if ($reporting !== null) {
            $directives['report-to'] = [$reporting['group']];
        }

        $serializedDirectives = [];

        foreach ($directives as $directive => $sources) {
            $serializedDirectives[] = $directive.' '.implode(' ', $sources);
        }

        return implode('; ', $serializedDirectives);
    }

    /**
     * @return array{group: string, endpoint: string, max_age: int}|null
     */
    private function reportingConfiguration(): ?array
    {
        if (! $this->cspReportingIsEnabled()) {
            return null;
        }

        $group = (string) config('security.csp.reporting.group', 'csp');
        $route = (string) config('security.csp.reporting.route', 'security.csp-reports');

        if (! preg_match('/^[a-z][a-z0-9_-]{0,31}$/', $group) || $route === '') {
            return null;
        }

        try {
            $path = route($route, absolute: false);
        } catch (Throwable) {
            return null;
        }

        $baseUrl = rtrim((string) config('app.url'), '/');
        $parts = parse_url($baseUrl);

        if (! is_array($parts)
            || ! in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            || ! isset($parts['host'])
            || ! filter_var($parts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || isset($parts['port']) && (! is_int($parts['port']) || $parts['port'] < 1 || $parts['port'] > 65_535)
            || ! in_array($parts['path'] ?? '', ['', '/'], true)
            || $path !== '/api/csp-reports') {
            return null;
        }

        if (($parts['scheme'] ?? null) !== 'https' && ! app()->environment(['local', 'testing'])) {
            return null;
        }

        $maxAge = min(604_800, max(60, (int) config('security.csp.reporting.max_age', 86_400)));

        return [
            'group' => $group,
            'endpoint' => $baseUrl.$path,
            'max_age' => $maxAge,
        ];
    }

    private function cspReportingIsEnabled(): bool
    {
        return (bool) config('security.csp.report_only', false);
    }

    /**
     * @return list<string>
     */
    private function trustedSources(string $directive, mixed $sources): array
    {
        if (! is_array($sources)) {
            return [];
        }

        $trustedSources = [];

        foreach ($sources as $source) {
            $trustedSource = $this->trustedSource($directive, $source);

            if ($trustedSource !== null && ! in_array($trustedSource, $trustedSources, true)) {
                $trustedSources[] = $trustedSource;
            }
        }

        return $trustedSources;
    }

    private function trustedSource(string $directive, mixed $source): ?string
    {
        if (! is_string($source)) {
            return null;
        }

        $source = trim($source);

        if ($source === "'self'") {
            return $source;
        }

        if ($source === 'data:' && in_array($directive, ['font-src', 'img-src'], true)) {
            return $source;
        }

        if ($source === '' || strpbrk($source, "*\r\n;") !== false) {
            return null;
        }

        $parts = parse_url($source);

        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || ! isset($parts['host'])
            || ! filter_var($parts['host'], FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)
            || isset($parts['user'])
            || isset($parts['pass'])
            || isset($parts['query'])
            || isset($parts['fragment'])
            || isset($parts['port']) && (! is_int($parts['port']) || $parts['port'] < 1 || $parts['port'] > 65_535)
            || ! in_array($parts['path'] ?? '', ['', '/'], true)) {
            return null;
        }

        return 'https://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
