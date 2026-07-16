<?php

namespace App\Livewire\Forms;

use App\Support\SiteContent;
use Illuminate\Validation\Rule;
use Livewire\Form;

class ConsultationRequestFormData extends Form
{
    public string $name = '';

    public string $email = '';

    public string $company = '';

    public string $service = '';

    public string $challenge = '';

    public string $website = '';

    public function rules(): array
    {
        $serviceKeys = collect(SiteContent::services())
            ->map(fn (array $service): string => (string) ($service['key'] ?? $service['id']))
            ->all();

        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:120'],
            'service' => [
                'required',
                Rule::in([...$serviceKeys, 'general']),
            ],
            'challenge' => ['required', 'string', 'min:20', 'max:3000'],
        ];
    }

    public function validationAttributes(): array
    {
        return [
            'name' => __('site.consultation.validation.name'),
            'email' => __('site.consultation.validation.email'),
            'company' => __('site.consultation.validation.company'),
            'service' => __('site.consultation.validation.service'),
            'challenge' => __('site.consultation.validation.challenge'),
        ];
    }
}
