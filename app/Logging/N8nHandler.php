<?php

namespace App\Logging;

use Illuminate\Support\Facades\Http;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;

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

        $payload = [
            'project_name' => $this->config['project'] ?? config('app.name', 'Laravel'),
            'environment' => config('app.env'),
            'level' => $record->level->name,
            'message' => $record->message,
            'file' => $exception ? $exception->getFile().':'.$exception->getLine() : 'N/A',
            'trace' => $exception ? $exception->getTraceAsString() : null,
            'url' => $request?->fullUrl(),
            'method' => $request?->method(),
            'user_info' => $user ? "ID: {$user->id} ({$user->email})" : 'Guest',
            'ip' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'request_id' => $request?->header('X-Request-ID') ?? 'N/A',
        ];

        try {
            Http::timeout((int) ($this->config['timeout'] ?? 3))
                ->withHeaders(['X-N8N-TOKEN' => $token])
                ->post($url, $payload);
        } catch (\Throwable $e) {
            error_log('[n8n-webhook-failed] '.$e->getMessage());
        }
    }
}
