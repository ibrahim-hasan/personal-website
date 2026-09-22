<?php

namespace App\Support;

final class AgentRafeeqConfiguration
{
    public static function widgetUrl(): ?string
    {
        if (! config('services.agent_rafeeq_widget.enabled')) {
            return null;
        }

        $url = self::safeUrl(config('services.agent_rafeeq_widget.script_url'), allowVersionQuery: true);
        $key = config('services.agent_rafeeq_widget.public_key');

        return $url !== null && is_string($key) && preg_match('/\Apk_[A-Za-z0-9_-]+\z/D', $key)
            ? $url : null;
    }

    public static function widgetOrigin(): ?string
    {
        $url = self::widgetUrl();

        return $url === null ? null : self::origin($url);
    }

    public static function safeUrl(mixed $value, bool $originOnly = false, bool $allowVersionQuery = false): ?string
    {
        if (! is_string($value) || $value !== trim($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $parts = parse_url($value);
        $local = app()->environment(['local', 'testing']);
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! is_array($parts) || ! in_array($parts['scheme'] ?? '', $local ? ['http', 'https'] : ['https'], true)
            || $host === '' || isset($parts['user']) || isset($parts['pass']) || isset($parts['fragment'])
            || (isset($parts['query']) && ($originOnly || ! $allowVersionQuery || preg_match('/\Av=[a-fA-F0-9]{16,64}\z/D', $parts['query']) !== 1))
            || ($originOnly && ! in_array($parts['path'] ?? '', ['', '/'], true))
            || (isset($parts['port']) && ($parts['port'] < 1 || $parts['port'] > 65535))
            || (! $local && ! self::isPublicHostname($host))) {
            return null;
        }

        return $originOnly ? self::origin($value) : $value;
    }

    private static function isPublicHostname(string $host): bool
    {
        return filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && ! str_ends_with($host, '.')
            && str_contains($host, '.')
            && filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) === false
            && preg_match('/(?:^|\.)(?:test|local|localhost|internal|invalid|example)\z/D', $host) !== 1
            && ! str_ends_with($host, '.home.arpa')
            && preg_match('/\.(?:[0-9]+|0x[0-9a-f]+)\z/D', $host) !== 1;
    }

    private static function origin(string $url): string
    {
        $parts = parse_url($url);

        return $parts['scheme'].'://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '');
    }
}
