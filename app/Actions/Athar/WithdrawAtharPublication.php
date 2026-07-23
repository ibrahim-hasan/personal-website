<?php

namespace App\Actions\Athar;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WithdrawAtharPublication
{
    public function handle(AtharPublicationVersion $version, Request $request): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $request): AtharPublicationVersion {
            $record = $version->lockForUpdate()->firstOrFail();
            abort_unless(AtharAccess::verified($request, $record->contribution->invitation), 403);
            $record->forceFill(['status' => AtharPublicationStatus::Withdrawn, 'withdrawn_at' => now()])->save();
            $record->consentEvents()->create(['contribution_id' => $record->contribution_id, 'event_type' => AtharConsentEventType::Withdrawn, 'snapshot_hash' => $record->snapshot_hash, 'approved_locales' => $record->approved_locales, 'placement' => $record->placement, 'placement_key' => $record->placement_key, 'identity_display' => $record->identity_display, 'privacy_notice_version' => config('legal.privacy_version'), 'verification_method' => AtharAccess::verificationMethod($record->contribution->invitation), 'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')), 'user_agent_hash' => hash('sha256', (string) $request->userAgent()), 'occurred_at' => now()]);
            $record->contribution->forceFill(['status' => AtharContributionStatus::Withdrawn])->save();

            return $record;
        });
    }
}
