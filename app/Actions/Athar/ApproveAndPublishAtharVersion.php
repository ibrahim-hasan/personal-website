<?php

namespace App\Actions\Athar;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharContributionStatus;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharInvitation;
use App\Models\AtharPublicationVersion;
use App\Support\AtharAccess;
use App\Support\AtharPlacementDestination;
use App\Support\AtharPublicationSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ApproveAndPublishAtharVersion
{
    public function handle(AtharPublicationVersion $version, Request $request, string $text, AtharIdentityDisplay $identityDisplay, string $displayName): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $request, $text, $identityDisplay, $displayName): AtharPublicationVersion {
            abort_unless($request->boolean('consent'), 422, __('athar.validation.consent_required'));
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $invitation = $record->contribution->invitation;
            abort_unless(AtharAccess::verified($request, $invitation), 403);
            abort_unless(in_array($record->status, [AtharPublicationStatus::AwaitingApproval, AtharPublicationStatus::Draft], true), 422);
            abort_unless(
                $record->version === (int) $record->contribution->publicationVersions()->max('version'),
                422,
            );
            $placementKey = AtharPlacementDestination::validatedKey($record->placement, $record->placement_key);
            abort_unless($placementKey === $record->placement_key, 422);
            abort_unless(AtharPublicationSnapshot::matches($record), 409);
            abort_unless(AtharPublicationSnapshot::matchesDestination($record), 409);
            $payload = $record->public_payload;
            $locale = array_key_first($payload);
            abort_unless(is_string($locale) && isset($payload[$locale]), 422);
            $payload[$locale]['text'] = $text;
            $payload[$locale]['identity_display'] = $identityDisplay->value;
            $payload[$locale]['display_name'] = $displayName;
            $record->forceFill([
                'public_payload' => $payload,
                'snapshot_hash' => AtharPublicationSnapshot::hash($payload),
                'identity_display' => $identityDisplay,
                'display_name' => $displayName !== '' ? $displayName : null,
                'status' => AtharPublicationStatus::Published,
                'published_at' => now(),
                'withdrawn_at' => null,
            ])->save();
            $record->consentEvents()->create(['contribution_id' => $record->contribution_id, 'event_type' => AtharConsentEventType::Approved, 'snapshot_hash' => $record->snapshot_hash, 'approved_locales' => $record->approved_locales, 'placement' => $record->placement, 'placement_key' => $record->placement_key, 'identity_display' => $record->identity_display, 'privacy_notice_version' => config('legal.privacy_version'), 'verification_method' => AtharAccess::verificationMethod($invitation), 'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')), 'user_agent_hash' => hash('sha256', (string) $request->userAgent()), 'occurred_at' => now()]);
            $this->withdrawSupersededVersions($record, $request, $invitation);
            $record->contribution->forceFill(['status' => AtharContributionStatus::Published])->save();
            $invitation->forceFill(['status' => AtharInvitationStatus::Completed])->save();

            return $record->fresh();
        });
    }

    private function withdrawSupersededVersions(
        AtharPublicationVersion $record,
        Request $request,
        AtharInvitation $invitation,
    ): void {
        $record->contribution
            ->publicationVersions()
            ->whereKeyNot($record->getKey())
            ->where('status', AtharPublicationStatus::Published)
            ->lockForUpdate()
            ->get()
            ->each(function (AtharPublicationVersion $previous) use ($request, $invitation): void {
                $previous->forceFill([
                    'status' => AtharPublicationStatus::Withdrawn,
                    'withdrawn_at' => now(),
                ])->save();
                $previous->consentEvents()->create([
                    'contribution_id' => $previous->contribution_id,
                    'event_type' => AtharConsentEventType::Withdrawn,
                    'snapshot_hash' => $previous->snapshot_hash,
                    'approved_locales' => $previous->approved_locales,
                    'placement' => $previous->placement,
                    'placement_key' => $previous->placement_key,
                    'identity_display' => $previous->identity_display,
                    'privacy_notice_version' => config('legal.privacy_version'),
                    'verification_method' => AtharAccess::verificationMethod($invitation),
                    'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                    'user_agent_hash' => hash('sha256', (string) $request->userAgent()),
                    'occurred_at' => now(),
                ]);
            });
    }
}
