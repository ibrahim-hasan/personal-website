<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Throwable;

class N8nHandler extends AbstractProcessingHandler
{
    public function __construct(
        private readonly array $config = [],
        string|int|Level $level = Level::Warning,
        bool $bubble = true,
    ) {
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $url = $this->config['url'] ?? null;
        $token = $this->config['token'] ?? null;

        if (! $url || ! $token) {
            return;
        }

        $request = app('request');
        $user = auth()->user();
        $exception = $record->context['exception'] ?? null;

        if (! $exception instanceof Throwable) {
            $exception = null;
        }
        $route = $request?->route();
        $routeUri = is_object($route) && method_exists($route, 'uri')
            ? $route->uri()
            : null;
        $routeName = is_object($route) && method_exists($route, 'getName')
            ? $route->getName()
            : null;

        $payload = [
            'project_name' => $this->config['project'] ?? config('app.name', 'Laravel'),
            'environment' => config('app.env'),
            'level' => $record->level->name,
            'message' => $exception
                ? class_basename($exception)
                : 'Application '.$record->level->name.' event',
            'event_fingerprint' => $exception
                ? null
                : substr(hash_hmac('sha256', $record->message, (string) config('app.key')), 0, 16),
            'file' => $exception ? basename($exception->getFile()).':'.$exception->getLine() : 'N/A',
            'route' => is_string($routeName) ? $routeName : $routeUri,
            'path_template' => is_string($routeUri) ? '/'.ltrim($routeUri, '/') : null,
            'method' => $request?->method(),
            'user_state' => $user ? 'Authenticated' : 'Guest',
            'request_id' => Str::limit(
                preg_replace('/[^A-Za-z0-9._-]/', '', (string) $request?->header('X-Request-ID')),
                100,
                '',
            ) ?: 'N/A',
        ];

        try {
            Http::timeout((int) ($this->config['timeout'] ?? 3))
                ->withHeaders(['X-N8N-TOKEN' => $token])
                ->post($url, $payload);
        } catch (Throwable $e) {
            error_log('[n8n-webhook-failed] '.$e->getMessage());
        }
    }
}
