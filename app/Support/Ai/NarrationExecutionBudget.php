<?php

namespace App\Support\Ai;

final class NarrationExecutionBudget
{
    /** @var list<int> */
    private const RETRY_DELAYS_MILLISECONDS = [1500, 3500];

    private const MAX_PROVIDER_TIMEOUT_SECONDS = 180;

    private const SHUTDOWN_RESERVE_SECONDS = 30;

    private const JOB_TIMEOUT_SECONDS = 600;

    public static function providerTimeout(): int
    {
        return max(1, min(
            (int) config('services.openai.timeout', self::MAX_PROVIDER_TIMEOUT_SECONDS),
            self::MAX_PROVIDER_TIMEOUT_SECONDS,
        ));
    }

    /** @return list<int> */
    public static function retryDelays(): array
    {
        return self::RETRY_DELAYS_MILLISECONDS;
    }

    public static function minimumJobTimeout(): int
    {
        $attempts = count(self::RETRY_DELAYS_MILLISECONDS) + 1;
        $delaySeconds = (int) ceil(array_sum(self::RETRY_DELAYS_MILLISECONDS) / 1000);

        return ($attempts * self::providerTimeout()) + $delaySeconds + self::SHUTDOWN_RESERVE_SECONDS;
    }

    public static function jobTimeout(): int
    {
        return max(self::JOB_TIMEOUT_SECONDS, self::minimumJobTimeout());
    }
}
