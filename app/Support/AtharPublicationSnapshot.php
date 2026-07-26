<?php

namespace App\Support;

use App\Models\AtharInvitation;
use App\Models\AtharPublicationVersion;
use JsonException;

final class AtharPublicationSnapshot
{
    /**
     * Add the exact destination wording to every locale stored in a new
     * contributor-controlled snapshot. That wording remains part of the
     * hashed snapshot rather than being reconstructed from mutable content.
     *
     * @param  array<string, array<string, mixed>>  $payload
     * @return array<string, array<string, mixed>>
     */
    public static function withDestination(AtharInvitation $invitation, array $payload): array
    {
        return collect($payload)
            ->map(function (array $entry, string $locale) use ($invitation): array {
                $entry['destination_placement'] = $invitation->placement->value;
                $entry['destination_key'] = $invitation->placement_key;
                $entry['destination_label'] = AtharPlacementDestination::label(
                    $invitation->placement,
                    $invitation->placement_key,
                    $locale,
                );

                return $entry;
            })
            ->all();
    }

    /**
     * @param  array<string, array<string, mixed>>  $payload
     */
    public static function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    public static function matches(AtharPublicationVersion $version): bool
    {
        try {
            return hash_equals($version->snapshot_hash, self::hash($version->public_payload));
        } catch (JsonException) {
            return false;
        }
    }

    /**
     * Verify the exact placement data that was frozen in a current snapshot.
     * Legacy snapshots predate these fields and remain readable through the
     * consent-event checks in the public presenter.
     */
    public static function matchesDestination(AtharPublicationVersion $version): bool
    {
        foreach ($version->public_payload as $entry) {
            if (! is_array($entry)) {
                return false;
            }

            $hasFrozenDestination = array_key_exists('destination_placement', $entry)
                || array_key_exists('destination_key', $entry);

            if (! $hasFrozenDestination) {
                continue;
            }

            if (($entry['destination_placement'] ?? null) !== $version->placement->value
                || ($entry['destination_key'] ?? null) !== $version->placement_key
                || ! is_string($entry['destination_label'] ?? null)
                || trim($entry['destination_label']) === '') {
                return false;
            }
        }

        return true;
    }

    public static function destinationLabel(AtharPublicationVersion $version, string $locale): string
    {
        $payload = $version->public_payload;
        $payloadLocale = array_key_exists($locale, $payload) ? $locale : array_key_first($payload);
        $snapshotLabel = is_string($payloadLocale)
            ? data_get($payload, $payloadLocale.'.destination_label')
            : null;

        if (is_string($snapshotLabel) && trim($snapshotLabel) !== '') {
            return $snapshotLabel;
        }

        return AtharPlacementDestination::label($version->placement, $version->placement_key, $locale);
    }
}
