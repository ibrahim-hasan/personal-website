<?php

namespace App\Actions\Athar;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RestoreAtharPublication
{
    public function handle(AtharPublicationVersion $version, Request $request): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $request): AtharPublicationVersion {
            $record = $version->lockForUpdate()->firstOrFail();
            abort_unless(AtharAccess::verified($request, $record->contribution->invitation), 403);
            abort_unless($record->status === AtharPublicationStatus::Withdrawn, 422);

            $record->forceFill(['status' => AtharPublicationStatus::Published, 'withdrawn_at' => null])->save();
            $record->consentEvents()->create([
                'contribution_id' => $record->contribution_id,
                'event_type' => AtharConsentEventType::Restored,
                'snapshot_hash' => $record->snapshot_hash,
                'approved_locales' => $record->approved_locales,
                'placement' => $record->placement,
                'placement_key' => $record->placement_key,
                'identity_display' => $record->identity_display,
                'privacy_notice_version' => config('legal.privacy_version'),
                'verification_method' => 'email_code',
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
                'occurred_at' => now(),
            ]);
            $record->contribution->forceFill(['status' => AtharContributionStatus::Published])->save();

            return $record;
        });
    }
}
