<?php

namespace App\Services\Security;

use DateTimeInterface;
use Illuminate\Support\Facades\Cache;

final class CspReportSignalStore
{
    /**
     * @var list<string>
     */
    private const array Directives = [
        'base-uri',
        'connect-src',
        'default-src',
        'font-src',
        'form-action',
        'frame-ancestors',
        'frame-src',
        'img-src',
        'media-src',
        'object-src',
        'other',
        'script-src',
        'style-src',
    ];

    /**
     * @var list<string>
     */
    private const array Categories = [
        'document',
        'network',
        'other',
        'script',
        'style',
    ];

    /**
     * @var list<array{directive: string, category: string}>
     */
    private const array SignalPairs = [
        ['directive' => 'base-uri', 'category' => 'document'],
        ['directive' => 'connect-src', 'category' => 'network'],
        ['directive' => 'default-src', 'category' => 'document'],
        ['directive' => 'font-src', 'category' => 'network'],
        ['directive' => 'form-action', 'category' => 'document'],
        ['directive' => 'frame-ancestors', 'category' => 'document'],
        ['directive' => 'frame-src', 'category' => 'document'],
        ['directive' => 'img-src', 'category' => 'network'],
        ['directive' => 'media-src', 'category' => 'network'],
        ['directive' => 'object-src', 'category' => 'document'],
        ['directive' => 'other', 'category' => 'other'],
        ['directive' => 'script-src', 'category' => 'script'],
        ['directive' => 'style-src', 'category' => 'style'],
    ];

    private const int RetentionMinutes = 15;

    public function record(string $directive, string $category): void
    {
        if (! $this->isControlledSignal($directive, $category)) {
            return;
        }

        $key = $this->keyAt(now(), $directive, $category);

        Cache::add($key, 0, now()->addMinutes(self::RetentionMinutes));
        Cache::increment($key);
    }

    public function countForCurrentMinute(string $directive, string $category): int
    {
        if (! $this->isControlledSignal($directive, $category)) {
            return 0;
        }

        return (int) Cache::get($this->keyAt(now(), $directive, $category), 0);
    }

    /**
     * @return list<array{directive: string, category: string, count: int}>
     */
    public function recentCounts(int $minutes): array
    {
        $minutes = min(self::RetentionMinutes, max(1, $minutes));
        $counts = [];

        foreach (self::SignalPairs as $signal) {
            $count = 0;

            for ($offset = 0; $offset < $minutes; $offset++) {
                $count += (int) Cache::get(
                    $this->keyAt(now()->startOfMinute()->subMinutes($offset), $signal['directive'], $signal['category']),
                    0,
                );
            }

            if ($count > 0) {
                $counts[] = [
                    ...$signal,
                    'count' => $count,
                ];
            }
        }

        return $counts;
    }

    private function isControlledSignal(string $directive, string $category): bool
    {
        return in_array($directive, self::Directives, true)
            && in_array($category, self::Categories, true);
    }

    private function keyAt(DateTimeInterface $at, string $directive, string $category): string
    {
        return sprintf(
            'security:csp-report:%s:%s:%s',
            $at->format('YmdHi'),
            $directive,
            $category,
        );
    }
}
