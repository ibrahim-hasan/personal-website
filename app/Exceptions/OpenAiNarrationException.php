<?php

namespace App\Exceptions;

use Illuminate\Http\Client\RequestException;
use RuntimeException;
use Throwable;

class OpenAiNarrationException extends RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $providerRequestId,
    ) {
        parent::__construct(sprintf(
            'OpenAI narration preparation failed%s (request %s).',
            $httpStatus > 0 ? ' with HTTP '.$httpStatus : '',
            $providerRequestId,
        ));
    }

    public static function fromThrowable(Throwable $exception): self
    {
        $current = $exception;

        do {
            if ($current instanceof RequestException && $current->response !== null) {
                return new self(
                    httpStatus: $current->response->status(),
                    providerRequestId: self::sanitizeRequestId(
                        $current->response->header('x-request-id', 'unavailable'),
                    ),
                );
            }

            $current = $current->getPrevious();
        } while ($current instanceof Throwable);

        return new self(
            httpStatus: 0,
            providerRequestId: 'unavailable',
        );
    }

    private static function sanitizeRequestId(string $requestId): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9._:-]/', '', mb_substr($requestId, 0, 120));

        return filled($sanitized) ? $sanitized : 'unavailable';
    }
}
