<?php

namespace App\Actions\Privacy;

use App\Actions\Athar\PurgeExpiredAtharData;
use App\Enums\CommentReportStatus;
use App\Enums\ContactInquiryStatus;
use App\Models\CommentReport;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PurgeExpiredPersonalData
{
    /** @return array{archived_inquiries: int, resolved_reports: int, athar_challenges: int, athar_contributions: int} */
    public function preview(?PurgeExpiredAtharData $athar = null): array
    {
        return [
            'archived_inquiries' => $this->expiredArchivedInquiries()->count(),
            'resolved_reports' => $this->expiredResolvedReports()->count(),
            'athar_challenges' => $athar?->preview()['challenges'] ?? 0,
            'athar_contributions' => $athar?->preview()['contributions'] ?? 0,
        ];
    }

    /** @return array{archived_inquiries: int, resolved_reports: int, athar_challenges: int, athar_contributions: int} */
    public function purge(?PurgeExpiredAtharData $athar = null): array
    {
        return [
            'archived_inquiries' => $this->deleteInChunks(
                $this->expiredArchivedInquiries(),
                ContactInquiry::class,
            ),
            'resolved_reports' => $this->deleteInChunks(
                $this->expiredResolvedReports(),
                CommentReport::class,
            ),
            ...($athar === null ? ['athar_challenges' => 0, 'athar_contributions' => 0] : $this->atharPurge($athar)),
        ];
    }

    /** @return array{athar_challenges: int, athar_contributions: int} */
    private function atharPurge(PurgeExpiredAtharData $athar): array
    {
        $purged = $athar->purge();

        return ['athar_challenges' => $purged['challenges'], 'athar_contributions' => $purged['contributions']];
    }

    /** @return Builder<ContactInquiry> */
    private function expiredArchivedInquiries(): Builder
    {
        return ContactInquiry::query()
            ->where('status', ContactInquiryStatus::Archived)
            ->where('received_at', '<', now()->subDays($this->retentionDays('archived_inquiries_days')));
    }

    /** @return Builder<CommentReport> */
    private function expiredResolvedReports(): Builder
    {
        return CommentReport::query()
            ->whereIn('status', [CommentReportStatus::Resolved, CommentReportStatus::Dismissed])
            ->whereNotNull('reviewed_at')
            ->where('reviewed_at', '<', now()->subDays($this->retentionDays('resolved_reports_days')));
    }

    /**
     * @param  Builder<Model>  $query
     * @param  class-string<Model>  $modelClass
     */
    private function deleteInChunks(Builder $query, string $modelClass): int
    {
        $deleted = 0;

        $query->select('id')->chunkById(100, function (Collection $records) use (&$deleted, $modelClass): void {
            $deleted += $modelClass::query()->whereKey($records->modelKeys())->delete();
        });

        return $deleted;
    }

    private function retentionDays(string $setting): int
    {
        return max(1, (int) config("legal.retention.{$setting}"));
    }
}
