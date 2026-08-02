<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class CanonicalizePublicUrl
{
    /**
     * Redirect duplicate public GET and HEAD URLs to their canonical path.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $rawPath = parse_url($request->getRequestUri(), PHP_URL_PATH);
        $path = '/'.ltrim(is_string($rawPath) ? $rawPath : $request->path(), '/');

        if ($this->isExcludedPath($path)) {
            return $next($request);
        }

        $canonicalPath = $this->canonicalPath($path);

        if ($canonicalPath === $path) {
            return $next($request);
        }

        $target = $canonicalPath;
        $query = $request->getQueryString();

        if (is_string($query) && $query !== '') {
            $target .= '?'.$query;
        }

        return redirect()->to($target, 301);
    }

    private function canonicalPath(string $path): string
    {
        if ($path === '/index.php') {
            return '/';
        }

        if ($path === '/en/index.php') {
            return '/en';
        }

        if ($path !== '/' && str_ends_with($path, '/')) {
            return rtrim($path, '/');
        }

        return $path;
    }

    private function isExcludedPath(string $path): bool
    {
        return $path === '/admin'
            || str_starts_with($path, '/admin/')
            || str_starts_with($path, '/api/')
            || $path === '/up'
            || str_starts_with($path, '/health/');
    }
}
