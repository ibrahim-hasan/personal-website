<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class OpenAiNarrationException extends RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly string $providerRequestId,
    ) {
        parent::__construct(sprintf(
            'OpenAI narration preparation failed with HTTP %d (request %s).',
            $httpStatus,
            $providerRequestId,
        ));
    }

    public static function fromResponse(Response $response): self
    {
        return new self(
            httpStatus: $response->status(),
            providerRequestId: $response->header('x-request-id', 'unavailable'),
        );
    }
}
