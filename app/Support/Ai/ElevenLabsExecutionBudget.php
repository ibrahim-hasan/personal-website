<?php

namespace App\Support\Ai;

use LogicException;

final class ElevenLabsExecutionBudget
{
    private const MAX_PROVIDER_TIMEOUT_SECONDS = 600;

    private const DEFAULT_FULL_JOB_TIMEOUT_SECONDS = 1560;

    private const DEFAULT_MAX_SEGMENTS = 3;

    private const FINALIZATION_RESERVE_SECONDS = 180;

    private const UNIQUE_LOCK_RESERVE_SECONDS = 60;

    public static function providerTimeout(): int
    {
        return max(1, min(
            (int) config('services.elevenlabs.timeout', 420),
            self::MAX_PROVIDER_TIMEOUT_SECONDS,
        ));
    }

    public static function connectTimeout(): int
    {
        return max(1, min(
            (int) config('services.elevenlabs.connect_timeout', 15),
            self::providerTimeout(),
        ));
    }

    public static function requestAttempts(): int
    {
        return 1;
    }

    public static function requestBudgetSeconds(): int
    {
        return self::providerTimeout();
    }

    public static function maxSegments(): int
    {
        return max(1, min(
            (int) config('services.elevenlabs.max_segments', self::DEFAULT_MAX_SEGMENTS),
            10,
        ));
    }

    public static function sampleJobTimeout(string $modelId): int
    {
        return (self::sampleMaxSegments($modelId) * self::requestBudgetSeconds())
            + self::FINALIZATION_RESERVE_SECONDS;
    }

    public static function minimumFullJobTimeout(): int
    {
        return (self::maxSegments() * self::requestBudgetSeconds())
            + self::FINALIZATION_RESERVE_SECONDS;
    }

    public static function fullJobTimeout(): int
    {
        $timeout = max(
            (int) config('services.elevenlabs.job_timeout', self::DEFAULT_FULL_JOB_TIMEOUT_SECONDS),
            self::minimumFullJobTimeout(),
        );

        self::ensureQueueRetryAfterExceeds($timeout);

        return $timeout;
    }

    public static function uniqueFor(int $jobTimeout): int
    {
        return max(
            (int) config('services.elevenlabs.unique_for', 1800),
            $jobTimeout + self::UNIQUE_LOCK_RESERVE_SECONDS,
        );
    }

    public static function stalledAfterSeconds(): int
    {
        return self::uniqueFor(self::fullJobTimeout());
    }

    private static function sampleMaxSegments(string $modelId): int
    {
        $sampleCharacters = max(1, (int) config('services.elevenlabs.sample_characters', 650));
        $modelCharacters = max(
            100,
            (int) config('services.elevenlabs.models.'.$modelId.'.max_characters', 4500),
        );

        return min(self::maxSegments(), (int) ceil($sampleCharacters / $modelCharacters));
    }

    private static function ensureQueueRetryAfterExceeds(int $jobTimeout): void
    {
        $connection = (string) config('services.elevenlabs.queue_connection', 'database');
        $retryAfter = config('queue.connections.'.$connection.'.retry_after');

        if (is_numeric($retryAfter) && (int) $retryAfter <= $jobTimeout) {
            throw new LogicException(sprintf(
                'The %s queue retry_after value must exceed the ElevenLabs job timeout of %d seconds.',
                $connection,
                $jobTimeout,
            ));
        }
    }
}
