<?php

namespace App\Http\Requests\Auth;

use App\Rules\TurnstileToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class ReaderForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        $turnstile = app(TurnstileToken::class);

        return [
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'cf-turnstile-response' => $turnstile->enabled() ? ['required', new TurnstileToken] : ['nullable'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim($this->string('email')->toString())),
            ]);
        }
    }
}
