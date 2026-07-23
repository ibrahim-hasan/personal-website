<?php

namespace App\Actions\Athar;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use App\Support\AtharAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApproveAndPublishAtharVersion
{
    public function handle(AtharPublicationVersion $version, Request $request): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $request): AtharPublicationVersion {
            abort_unless($request->boolean('consent'), 422, __('athar.validation.consent_required'));
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $invitation = $record->contribution->invitation;
            abort_unless(AtharAccess::verified($request, $invitation), 403);
            abort_unless(in_array($record->status, [AtharPublicationStatus::AwaitingApproval, AtharPublicationStatus::Draft], true), 422);
            $payload = $record->public_payload;
            $hash = hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            abort_unless(hash_equals($record->snapshot_hash, $hash), 409);
            $record->forceFill(['status' => AtharPublicationStatus::Published, 'published_at' => now(), 'withdrawn_at' => null])->save();
            $record->consentEvents()->create(['contribution_id' => $record->contribution_id, 'event_type' => AtharConsentEventType::Approved, 'snapshot_hash' => $record->snapshot_hash, 'approved_locales' => $record->approved_locales, 'placement' => $record->placement, 'placement_key' => $record->placement_key, 'identity_display' => $record->identity_display, 'privacy_notice_version' => config('legal.privacy_version'), 'verification_method' => AtharAccess::verificationMethod($invitation), 'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')), 'user_agent_hash' => hash('sha256', (string) $request->userAgent()), 'occurred_at' => now()]);
            $record->contribution->forceFill(['status' => AtharContributionStatus::Published])->save();

            return $record;
        });
    }
}
