<?php

namespace App\Actions\Consultation;

use App\Support\SiteContent;
use Illuminate\Validation\Rule;

class ConsultationRequestRules
{
    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'company' => ['nullable', 'string', 'max:160'],
            'role' => ['nullable', 'string', 'max:160'],
            'service' => ['required', Rule::in($this->serviceKeys())],
            'challenge' => ['required', 'string', 'min:40', 'max:4000'],
            'timing' => ['nullable', 'string', 'max:500'],
        ];
    }

    /** @return array<string, string> */
    public function validationAttributes(): array
    {
        return [
            'name' => __('site.consultation.validation.name'),
            'email' => __('site.consultation.validation.email'),
            'company' => __('site.consultation.validation.company'),
            'role' => __('site.consultation.validation.role'),
            'service' => __('site.consultation.validation.service'),
            'challenge' => __('site.consultation.validation.challenge'),
            'timing' => __('site.consultation.validation.timing'),
        ];
    }

    /** @return list<array{key: string, id: string, name: string}> */
    public function availableServices(): array
    {
        return [
            ...collect(SiteContent::services())
                ->map(fn (array $service): array => [
                    'key' => (string) ($service['key'] ?? $service['id']),
                    'id' => (string) ($service['id'] ?? $service['key']),
                    'name' => (string) $service['name'],
                ])
                ->all(),
            [
                'key' => 'general',
                'id' => 'general',
                'name' => __('site.consultation.general_service'),
            ],
        ];
    }

    /** @return list<string> */
    public function serviceKeys(): array
    {
        return collect($this->availableServices())
            ->pluck('key')
            ->all();
    }

    public function serviceLabel(string $serviceKey): string
    {
        return (string) (collect($this->availableServices())
            ->firstWhere('key', $serviceKey)['name'] ?? __('site.consultation.general_service'));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{name: string, email: string, company: string|null, role: string|null, service: string, challenge: string, timing: string|null}
     */
    public function normalize(array $payload): array
    {
        return [
            'name' => trim((string) $payload['name']),
            'email' => trim((string) $payload['email']),
            'company' => $this->nullableString($payload['company'] ?? null),
            'role' => $this->nullableString($payload['role'] ?? null),
            'service' => (string) $payload['service'],
            'challenge' => trim((string) $payload['challenge']),
            'timing' => $this->nullableString($payload['timing'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
