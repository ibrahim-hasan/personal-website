<?php

namespace App\Support;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationStatus;
use App\Models\AtharPublicationVersion;
use Illuminate\Support\Facades\Schema;

final class AtharPublicProof
{
    /** @return list<array{text: string, context: string, name: string, locale: string}> */
    public static function forPlacement(AtharPlacement $placement, string $locale, ?string $placementKey = null): array
    {
        if (! Schema::hasTable('athar_publication_versions')) {
            return [];
        }

        return AtharPublicationVersion::query()
            ->with(['contribution.invitation', 'consentEvents'])
            ->where('status', AtharPublicationStatus::Published)
            ->where('placement', $placement)
            ->when($placementKey !== null, fn ($query) => $query->where('placement_key', $placementKey))
            ->latest('published_at')
            ->get()
            ->filter(function (AtharPublicationVersion $version) use ($locale): bool {
                if (! in_array($locale, $version->approved_locales ?? [], true)) {
                    return false;
                }
                $approved = $version->consentEvents
                    ->where('event_type', AtharConsentEventType::Approved)
                    ->where('snapshot_hash', $version->snapshot_hash)
                    ->sortByDesc('occurred_at')
                    ->first();
                $withdrawn = $approved !== null
                    && $version->consentEvents
                        ->where('event_type', AtharConsentEventType::Withdrawn)
                        ->filter(fn ($withdrawn) => $version->consentEvents
                            ->where('event_type', AtharConsentEventType::Restored)
                            ->where('occurred_at', '>', $withdrawn->occurred_at)
                            ->isEmpty())
                        ->where('occurred_at', '>', $approved->occurred_at)
                        ->isNotEmpty();

                return $approved !== null && ! $withdrawn;
            })
            ->map(function (AtharPublicationVersion $version) use ($locale): array {
                $payload = $version->public_payload[$locale] ?? [];
                $invitation = $version->contribution->invitation;
                $name = match ($version->identity_display) {
                    AtharIdentityDisplay::FullName => (string) $invitation->recipient_name,
                    AtharIdentityDisplay::FirstName => trim((string) preg_split('/\s+/u', (string) $invitation->recipient_name)[0]),
                    default => '',
                };

                return ['text' => (string) ($payload['text'] ?? ''), 'context' => (string) ($payload['context'] ?? ''), 'name' => $name, 'locale' => $locale];
            })->filter(fn (array $card): bool => $card['text'] !== '')->values()->all();
    }
}
