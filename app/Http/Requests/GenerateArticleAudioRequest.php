<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateArticleAudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update intellectual_libraries') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'model_id' => $this->input('model_id', config('services.elevenlabs.model_id')),
        ]);
    }

    /** @return array<string, array<mixed>> */
    public function rules(): array
    {
        return [
            'model_id' => [
                'required',
                'string',
                Rule::in(array_keys((array) config('services.elevenlabs.models', []))),
            ],
        ];
    }
}
