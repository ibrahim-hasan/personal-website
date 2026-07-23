<?php

namespace App\Actions\Athar;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EditAtharPublicationVersion
{
    /**
     * @param  array{identity_display?: AtharIdentityDisplay, text?: string}  $changes
     * @param  Request|null  $request  HTTP context for the consent audit entry
     */
    public function handle(AtharPublicationVersion $version, array $changes, ?Request $request = null, ?User $editor = null): AtharPublicationVersion
    {
        return DB::transaction(function () use ($version, $changes, $request, $editor): AtharPublicationVersion {
            $record = AtharPublicationVersion::query()
                ->whereKey($version->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless(in_array($record->status, [AtharPublicationStatus::Published, AtharPublicationStatus::AwaitingApproval, AtharPublicationStatus::Hidden], true), 422);

            if (array_key_exists('identity_display', $changes) && $changes['identity_display'] instanceof AtharIdentityDisplay) {
                $record->forceFill(['identity_display' => $changes['identity_display']]);
            }

            $textChanged = false;
            if (array_key_exists('text', $changes)) {
                $payload = $record->public_payload;
                $locale = array_key_first($payload);
                if (is_string($locale) && isset($payload[$locale])) {
                    $payload[$locale]['text'] = (string) $changes['text'];
                    $record->forceFill([
                        'public_payload' => $payload,
                        'snapshot_hash' => hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
                    ]);
                    $textChanged = true;
                }
            }

            $record->save();

            // An admin edit supersedes the contributor-approved snapshot. Record an
            // approved consent event bound to the new snapshot so the public proof
            // (which requires a matching approved event) keeps showing the version.
            if ($textChanged || $editor !== null) {
                $record->consentEvents()->create([
                    'contribution_id' => $record->contribution_id,
                    'event_type' => AtharConsentEventType::Approved,
                    'snapshot_hash' => $record->snapshot_hash,
                    'approved_locales' => $record->approved_locales,
                    'placement' => $record->placement,
                    'placement_key' => $record->placement_key,
                    'identity_display' => $record->identity_display,
                    'privacy_notice_version' => config('legal.privacy_version'),
                    'verification_method' => 'admin_edit',
                    'ip_hash' => $request ? hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')) : null,
                    'user_agent_hash' => $request ? hash('sha256', (string) $request->userAgent()) : null,
                    'occurred_at' => now(),
                ]);

                if ($editor !== null) {
                    $record->forceFill(['reviewed_by' => $editor->getKey()])->save();
                }
            }

            return $record->fresh();
        });
    }
}
