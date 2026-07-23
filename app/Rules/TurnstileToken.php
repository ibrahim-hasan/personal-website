<?php

namespace App\Rules;

use App\Support\Turnstile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\Request;

/**
 * Validates the `cf-turnstile-response` field against Cloudflare's siteverify.
 *
 * Use on FormRequests for plain HTML forms (register, login, forgot password,
 * Athar access). For Livewire components, call {@see Turnstile::verify()}
 * directly inside the action handler instead.
 *
 * @see Turnstile::enabled()
 */
class TurnstileToken implements ValidationRule
{
    private readonly Turnstile $turnstile;

    private readonly Request $request;

    public function __construct()
    {
        $this->turnstile = app(Turnstile::class);
        $this->request = app(Request::class);
    }

    /**
     * Whether Turnstile verification is active (site key + secret configured).
     * Lets FormRequests switch the field between required and nullable.
     */
    public function enabled(): bool
    {
        return $this->turnstile->enabled();
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('validation.turnstile');

            return;
        }

        if (! $this->turnstile->verify($value, $this->turnstile->clientIp($this->request))) {
            $fail('validation.turnstile');
        }
    }
}
