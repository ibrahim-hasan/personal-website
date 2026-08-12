<?php

namespace App\Console\Commands;

use App\Services\WebsitePerformance\WebsitePerformanceReporter;
use App\Services\WebsitePerformance\WebsitePerformanceSnapshotStore;
use App\Services\WebsitePerformance\WebsitePerformanceSourceException;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use JsonException;
use Throwable;

#[Signature('website:performance-report {--days=28 : Number of days per comparison window, from 1 to 366} {--end-date= : Inclusive report end date, YYYY-MM-DD} {--no-snapshot : Do not save the sanitized report snapshot}')]
#[Description('Produce a read-only, aggregate-only website performance report')]
class WebsitePerformanceReport extends Command
{
    public function handle(WebsitePerformanceReporter $reporter, WebsitePerformanceSnapshotStore $snapshots): int
    {
        $timezone = $this->timezone();

        if ($timezone === null) {
            return $this->outputUnavailable('timezone_configuration_unavailable');
        }

        $days = $this->days();

        if ($days === null) {
            return $this->outputUnavailable('invalid_days');
        }

        $endDate = $this->endDate($timezone);

        if ($endDate === null) {
            return $this->outputUnavailable('invalid_end_date', $timezone);
        }

        try {
            $report = $reporter->report($days, $endDate, $timezone);
        } catch (Throwable) {
            return $this->outputUnavailable('report_unavailable', $timezone, $endDate->toDateString());
        }

        $exitCode = $reporter->exitCode($report);

        if ($this->option('no-snapshot')) {
            $report['snapshot'] = ['status' => 'skipped'];
        } else {
            try {
                $report['snapshot'] = [
                    'status' => 'written',
                    'path' => $snapshots->persist($report),
                ];
            } catch (WebsitePerformanceSourceException $exception) {
                $report['snapshot'] = [
                    'status' => 'unavailable',
                    'warning' => $exception->reason,
                ];
                if ($exitCode !== self::FAILURE) {
                    $report['status'] = 'partial';
                    $exitCode = self::INVALID;
                }
            } catch (Throwable) {
                $report['snapshot'] = [
                    'status' => 'unavailable',
                    'warning' => 'snapshot_unavailable',
                ];
                if ($exitCode !== self::FAILURE) {
                    $report['status'] = 'partial';
                    $exitCode = self::INVALID;
                }
            }
        }

        $this->writeJson($report);

        return $exitCode;
    }

    private function days(): ?int
    {
        $days = trim((string) $this->option('days'));

        if (! ctype_digit($days)) {
            return null;
        }

        $value = (int) $days;

        return $value >= 1 && $value <= 366 ? $value : null;
    }

    private function timezone(): ?string
    {
        $timezone = trim((string) config('services.website_performance.timezone', 'Asia/Riyadh'));

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : null;
    }

    private function endDate(string $timezone): ?CarbonImmutable
    {
        $value = trim((string) $this->option('end-date'));

        if ($value === '') {
            return CarbonImmutable::now($timezone)->subDays(3)->startOfDay();
        }

        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value, $timezone);
        } catch (Throwable) {
            return null;
        }

        if ($date === null || $date->toDateString() !== $value || $date->greaterThan(CarbonImmutable::now($timezone)->startOfDay())) {
            return null;
        }

        return $date;
    }

    private function outputUnavailable(string $reason, string $timezone = 'Asia/Riyadh', ?string $cutoff = null): int
    {
        $this->writeJson([
            'schema_version' => 1,
            'generated_at' => now($timezone)->toAtomString(),
            'timezone' => $timezone,
            'data_cutoff' => $cutoff,
            'status' => 'unavailable',
            'periods' => null,
            'sources' => [],
            'quality' => [
                'source_statuses' => [],
                'flags' => [],
            ],
            'warnings' => [$reason],
            'snapshot' => ['status' => 'skipped'],
        ]);

        return self::FAILURE;
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function writeJson(array $report): void
    {
        try {
            $json = json_encode(
                $report,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            );
        } catch (JsonException) {
            $json = '{"schema_version":1,"status":"unavailable","warnings":["serialization_unavailable"]}';
        }

        $this->output->writeln($json);
    }
}
