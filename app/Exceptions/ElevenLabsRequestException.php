<?php

namespace App\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

class ElevenLabsRequestException extends RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        public readonly ?string $providerCode,
        public readonly string $providerRequestId,
    ) {
        parent::__construct($this->safeMessage());
    }

    public static function fromResponse(Response $response, string $fallbackRequestId = 'unavailable'): self
    {
        $providerCode = self::sanitizeToken($response->json('detail.code'))
            ?? self::sanitizeToken($response->json('detail.status'))
            ?? self::sanitizeToken($response->json('detail.type'));
        $providerRequestId = self::sanitizeToken($response->json('detail.request_id'))
            ?? self::sanitizeToken($fallbackRequestId)
            ?? 'unavailable';

        return new self(
            httpStatus: $response->status(),
            providerCode: $providerCode,
            providerRequestId: $providerRequestId,
        );
    }

    private function safeMessage(): string
    {
        $summary = match (true) {
            $this->httpStatus === 401 => 'ElevenLabs rejected the API key or the key is missing a required permission.',
            $this->httpStatus === 402 => 'ElevenLabs credits are insufficient or the API key credit quota has been reached. Check Billing and the API key credit limit before retrying.',
            $this->httpStatus === 403 => 'The ElevenLabs API key cannot access the selected voice, model, or feature.',
            $this->httpStatus === 422 => 'ElevenLabs rejected the speech input or generation settings.',
            $this->httpStatus === 429 => 'The ElevenLabs rate or concurrency limit remained exceeded after retrying.',
            $this->httpStatus >= 500 => 'ElevenLabs is temporarily unavailable.',
            default => 'ElevenLabs rejected the speech generation request.',
        };

        $context = ['HTTP '.$this->httpStatus];

        if ($this->providerCode !== null) {
            $context[] = 'code '.$this->providerCode;
        }

        if ($this->providerRequestId !== 'unavailable') {
            $context[] = 'request '.$this->providerRequestId;
        }

        return $summary.' ('.implode(', ', $context).').';
    }

    private static function sanitizeToken(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 128 || preg_match('/\A[A-Za-z0-9._:-]+\z/', $value) !== 1) {
            return null;
        }

        return $value;
    }
}
