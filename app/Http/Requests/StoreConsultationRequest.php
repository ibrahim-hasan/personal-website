<?php

namespace App\Http\Requests;

use App\Actions\Consultation\ConsultationRequestRules;
use App\Rules\TurnstileToken;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        $turnstile = app(TurnstileToken::class);

        return [
            ...app(ConsultationRequestRules::class)->rules(),
            'website' => ['nullable', 'string'],
            'submission_token' => ['required', 'string', 'size:64'],
            'cf-turnstile-response' => $turnstile->enabled()
                ? ['required', new TurnstileToken]
                : ['nullable'],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return app(ConsultationRequestRules::class)->validationAttributes();
    }

    /** @return array<string, mixed> */
    public function consultationPayload(): array
    {
        return $this->safe()->only([
            'name',
            'email',
            'company',
            'role',
            'service',
            'challenge',
            'timing',
        ]);
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash(
            'consultation.analytics_error',
            $validator->errors()->has('cf-turnstile-response') ? 'turnstile' : 'validation',
        );

        parent::failedValidation($validator);
    }

    protected $dontFlash = [
        'website',
        'submission_token',
        'cf-turnstile-response',
    ];
}
