<?php

namespace App\Actions\Privacy;

use App\Enums\CommentReportStatus;
use App\Enums\ContactInquiryStatus;
use App\Models\CommentReport;
use App\Models\ContactInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class PurgeExpiredPersonalData
{
    /** @return array{archived_inquiries: int, resolved_reports: int} */
    public function preview(): array
    {
        return [
            'archived_inquiries' => $this->expiredArchivedInquiries()->count(),
            'resolved_reports' => $this->expiredResolvedReports()->count(),
        ];
    }

    /** @return array{archived_inquiries: int, resolved_reports: int} */
    public function purge(): array
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
        ];
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
