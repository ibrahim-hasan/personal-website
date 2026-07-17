<?php

namespace App\Http\Requests\Website;

use App\Models\User;
use Illuminate\Foundation\Http\Attributes\ErrorBag;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

#[ErrorBag('profile')]
class UpdateReaderAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        /** @var User $reader */
        $reader = $this->user();

        return [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($reader),
            ],
            'current_password' => [
                Rule::requiredIf($this->emailWillChange($reader)),
                'nullable',
                'string',
                'current_password:web',
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name')) {
            $this->merge([
                'name' => trim($this->string('name')->toString()),
            ]);
        }

        if ($this->has('email')) {
            $this->merge([
                'email' => Str::lower(trim($this->string('email')->toString())),
            ]);
        }
    }

    private function emailWillChange(User $reader): bool
    {
        return Str::lower(trim($this->string('email')->toString())) !== Str::lower($reader->email);
    }
}
