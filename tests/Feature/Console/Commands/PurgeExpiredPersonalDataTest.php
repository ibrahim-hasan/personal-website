<?php

namespace Tests\Feature\Console\Commands;

use App\Enums\CommentReportStatus;
use App\Enums\ContactInquiryStatus;
use App\Models\CommentReport;
use App\Models\ContactInquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurgeExpiredPersonalDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_preview_is_non_destructive_even_when_records_are_eligible(): void
    {
        config()->set('legal.retention.archived_inquiries_days', 30);
        config()->set('legal.retention.resolved_reports_days', 30);

        $inquiry = ContactInquiry::factory()->create([
            'status' => ContactInquiryStatus::Archived,
            'received_at' => now()->subDays(31),
        ]);
        $report = CommentReport::factory()->create([
            'status' => CommentReportStatus::Resolved,
            'reviewed_at' => now()->subDays(31),
        ]);

        $this->artisan('privacy:purge-expired-data')
            ->expectsOutputToContain('Eligible records: 1 archived inquiries; 1 resolved or dismissed reports.')
            ->expectsOutputToContain('Preview only.')
            ->assertExitCode(0);

        $this->assertModelExists($inquiry);
        $this->assertModelExists($report);
    }

    public function test_force_requires_the_explicit_retention_enablement_flag(): void
    {
        config()->set('legal.retention.enabled', false);
        config()->set('legal.retention.archived_inquiries_days', 30);

        $inquiry = ContactInquiry::factory()->create([
            'status' => ContactInquiryStatus::Archived,
            'received_at' => now()->subDays(31),
        ]);

        $this->artisan('privacy:purge-expired-data', ['--force' => true])
            ->expectsOutputToContain('Deletion is disabled.')
            ->assertExitCode(1);

        $this->assertModelExists($inquiry);
    }

    public function test_force_purges_only_archived_inquiries_and_reviewed_reports_past_their_retention_period(): void
    {
        config()->set('legal.retention.enabled', true);
        config()->set('legal.retention.archived_inquiries_days', 30);
        config()->set('legal.retention.resolved_reports_days', 30);

        $expiredInquiry = ContactInquiry::factory()->create([
            'status' => ContactInquiryStatus::Archived,
            'received_at' => now()->subDays(31),
        ]);
        $recentArchivedInquiry = ContactInquiry::factory()->create([
            'status' => ContactInquiryStatus::Archived,
            'received_at' => now()->subDays(29),
        ]);
        $activeInquiry = ContactInquiry::factory()->create([
            'status' => ContactInquiryStatus::Replied,
            'received_at' => now()->subDays(90),
        ]);
        $expiredReport = CommentReport::factory()->create([
            'status' => CommentReportStatus::Dismissed,
            'reviewed_at' => now()->subDays(31),
        ]);
        $pendingReport = CommentReport::factory()->create([
            'status' => CommentReportStatus::Pending,
            'reviewed_at' => null,
        ]);

        $this->artisan('privacy:purge-expired-data', ['--force' => true])
            ->expectsOutputToContain('Purged 1 archived inquiries and 1 resolved or dismissed reports.')
            ->assertExitCode(0);

        $this->assertModelMissing($expiredInquiry);
        $this->assertModelMissing($expiredReport);
        $this->assertModelExists($recentArchivedInquiry);
        $this->assertModelExists($activeInquiry);
        $this->assertModelExists($pendingReport);

        $this->artisan('privacy:purge-expired-data', ['--force' => true])
            ->expectsOutputToContain('Purged 0 archived inquiries and 0 resolved or dismissed reports.')
            ->assertExitCode(0);
    }
}
