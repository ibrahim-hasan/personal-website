<?php

namespace App\Actions\Consultation;

use Illuminate\Support\Str;

class ConsultationSubmissionToken
{
    private const string SESSION_KEY = 'consultation.submission_token';

    public function current(): string
    {
        $token = session(self::SESSION_KEY);

        if (is_string($token) && strlen($token) === 64) {
            return $token;
        }

        return $this->rotate();
    }

    public function rotate(): string
    {
        $token = Str::random(64);

        session([self::SESSION_KEY => $token]);

        return $token;
    }

    public function matches(string $token): bool
    {
        return hash_equals($this->current(), $token);
    }

    public function hash(string $token): string
    {
        return hash_hmac('sha256', $token, (string) config('app.key'));
    }
}
