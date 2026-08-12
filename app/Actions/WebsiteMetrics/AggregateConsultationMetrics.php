<?php

namespace App\Actions\WebsiteMetrics;

use App\Enums\ContactInquiryStatus;
use App\Http\Requests\Api\WebsiteMetricsRequest;
use App\Models\ContactInquiry;
use Carbon\CarbonImmutable;
use DateTimeZone;

class AggregateConsultationMetrics
{
    /**
     * @return array{
     *     total: int,
     *     by_day: list<array{date: string, count: int}>,
     *     by_locale: list<array{locale: string, count: int}>,
     *     by_service: list<array{service_key: string, count: int}>,
     *     by_status: list<array{status: string, count: int}>,
     *     responded: int,
     *     response_rate: float|null
     * }
     */
    public function handle(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $timezone = new DateTimeZone(WebsiteMetricsRequest::Timezone);
        $byDay = $this->initialDayCounts($startDate, $endDate);
        $byLocale = [];
        $byService = [];
        $byStatus = array_fill_keys(
            array_map(static fn (ContactInquiryStatus $status): string => $status->value, ContactInquiryStatus::cases()),
            0,
        );
        $total = 0;
        $responded = 0;

        ContactInquiry::query()
            ->select(['received_at', 'locale', 'service_key', 'status', 'replied_at'])
            ->whereBetween('received_at', [
                $startDate->startOfDay()->utc(),
                $endDate->endOfDay()->utc(),
            ])
            ->orderBy('received_at')
            ->cursor()
            ->each(function (ContactInquiry $inquiry) use (&$byDay, &$byLocale, &$byService, &$byStatus, &$total, &$responded, $timezone): void {
                $date = $inquiry->received_at->setTimezone($timezone)->toDateString();
                $locale = (string) $inquiry->locale;
                $serviceKey = (string) $inquiry->service_key;
                $status = $inquiry->status->value;

                $byDay[$date] = ($byDay[$date] ?? 0) + 1;
                $byLocale[$locale] = ($byLocale[$locale] ?? 0) + 1;
                $byService[$serviceKey] = ($byService[$serviceKey] ?? 0) + 1;
                $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;
                $total++;

                if ($inquiry->replied_at !== null) {
                    $responded++;
                }
            });

        ksort($byLocale);
        ksort($byService);

        return [
            'total' => $total,
            'by_day' => $this->rows($byDay, 'date'),
            'by_locale' => $this->rows($byLocale, 'locale'),
            'by_service' => $this->rows($byService, 'service_key'),
            'by_status' => $this->rows($byStatus, 'status'),
            'responded' => $responded,
            'response_rate' => $total === 0 ? null : round($responded / $total, 4),
        ];
    }

    /** @return array<string, int> */
    private function initialDayCounts(CarbonImmutable $startDate, CarbonImmutable $endDate): array
    {
        $days = [];

        for ($date = $startDate->startOfDay(); $date->lessThanOrEqualTo($endDate); $date = $date->addDay()) {
            $days[$date->toDateString()] = 0;
        }

        return $days;
    }

    /**
     * @param  array<string, int>  $counts
     * @return list<array{count: int}>
     */
    private function rows(array $counts, string $key): array
    {
        return collect($counts)
            ->map(static fn (int $count, string $value): array => [$key => $value, 'count' => $count])
            ->values()
            ->all();
    }
}
