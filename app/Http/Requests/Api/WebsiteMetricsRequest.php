<?php

namespace App\Http\Requests\Api;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use LogicException;

class WebsiteMetricsRequest extends FormRequest
{
    public const string Timezone = 'Asia/Riyadh';

    private const int MaximumDays = 366;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'start' => ['bail', 'required', 'date_format:Y-m-d'],
            'end' => ['bail', 'required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['start', 'end'])) {
                    return;
                }

                $start = $this->parseDate('start');
                $end = $this->parseDate('end');

                if ($start === null || $end === null) {
                    $validator->errors()->add('start', 'The start and end dates must be valid ISO dates.');

                    return;
                }

                $today = CarbonImmutable::now(self::Timezone)->startOfDay();

                if ($start->greaterThan($today)) {
                    $validator->errors()->add('start', 'The start date must not be in the future.');
                }

                if ($end->greaterThan($today)) {
                    $validator->errors()->add('end', 'The end date must not be in the future.');
                }

                if ($start->greaterThan($end)) {
                    $validator->errors()->add('start', 'The start date must be on or before the end date.');

                    return;
                }

                if ($start->diffInDays($end) + 1 > self::MaximumDays) {
                    $validator->errors()->add('end', 'The requested date range may not exceed 366 days.');
                }
            },
        ];
    }

    public function startDate(): CarbonImmutable
    {
        return $this->parsedDateOrFail('start');
    }

    public function endDate(): CarbonImmutable
    {
        return $this->parsedDateOrFail('end');
    }

    private function parsedDateOrFail(string $key): CarbonImmutable
    {
        return $this->parseDate($key)
            ?? throw new LogicException("The {$key} date must be validated before use.");
    }

    private function parseDate(string $key): ?CarbonImmutable
    {
        $value = $this->input($key);

        if (! is_string($value)) {
            return null;
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, self::Timezone);
        } catch (\Throwable) {
            return null;
        }

        return $date->format('Y-m-d') === $value ? $date : null;
    }
}
