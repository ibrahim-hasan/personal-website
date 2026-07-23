<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\TurnstileToken;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ReaderRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'terms_accepted' => ['accepted'],
            'cf-turnstile-response' => app(TurnstileToken::class)->enabled() ? ['required', new TurnstileToken] : ['nullable'],
        ];
    }
}
