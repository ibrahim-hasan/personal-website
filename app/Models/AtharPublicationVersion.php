<?php

namespace App\Models;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use App\Enums\AtharPublicationOrigin;
use App\Enums\AtharPublicationStatus;
use Database\Factories\AtharPublicationVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtharPublicationVersion extends Model
{
    /** @use HasFactory<AtharPublicationVersionFactory> */
    use HasFactory;

    protected $fillable = ['contribution_id', 'version', 'status', 'origin', 'public_payload', 'snapshot_hash', 'placement', 'placement_key', 'identity_display', 'approved_locales', 'sent_for_approval_at', 'published_at', 'hidden_at', 'withdrawn_at', 'reviewed_by'];

    protected $hidden = ['public_payload', 'snapshot_hash'];

    protected function casts(): array
    {
        return [
            'status' => AtharPublicationStatus::class,
            'origin' => AtharPublicationOrigin::class,
            'placement' => AtharPlacement::class,
            'identity_display' => AtharIdentityDisplay::class,
            'public_payload' => 'encrypted:array',
            'approved_locales' => 'array',
            'sent_for_approval_at' => 'datetime',
            'published_at' => 'datetime',
            'hidden_at' => 'datetime',
            'withdrawn_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AtharContribution, $this> */
    public function contribution(): BelongsTo
    {
        return $this->belongsTo(AtharContribution::class);
    }

    /** @return BelongsTo<User, $this> */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /** @return HasMany<AtharPublicationConsentEvent, $this> */
    public function consentEvents(): HasMany
    {
        return $this->hasMany(AtharPublicationConsentEvent::class, 'publication_version_id');
    }

    public function isPublicFor(string $locale): bool
    {
        return $this->status === AtharPublicationStatus::Published
            && $this->withdrawn_at === null
            && in_array($locale, $this->approved_locales ?? [], true)
            && $this->consentEvents()
                ->where('event_type', 'approved')
                ->where('snapshot_hash', $this->snapshot_hash)
                ->whereNotExists(fn ($query) => $query->from('athar_publication_consent_events as withdrawals')
                    ->whereColumn('withdrawals.publication_version_id', 'athar_publication_consent_events.publication_version_id')
                    ->where('withdrawals.event_type', 'withdrawn')
                    ->whereNotExists(fn ($query) => $query->from('athar_publication_consent_events as restores')
                        ->whereColumn('restores.publication_version_id', 'withdrawals.publication_version_id')
                        ->where('restores.event_type', 'restored')
                        ->where(function ($query): void {
                            $query->whereColumn('restores.occurred_at', '>', 'withdrawals.occurred_at')
                                ->orWhere(function ($query): void {
                                    $query->whereColumn('restores.occurred_at', 'withdrawals.occurred_at')
                                        ->whereColumn('restores.id', '>', 'withdrawals.id');
                                });
                        }))
                    ->where(function ($query): void {
                        $query->whereColumn('withdrawals.occurred_at', '>', 'athar_publication_consent_events.occurred_at')
                            ->orWhere(function ($query): void {
                                $query->whereColumn('withdrawals.occurred_at', 'athar_publication_consent_events.occurred_at')
                                    ->whereColumn('withdrawals.id', '>', 'athar_publication_consent_events.id');
                            });
                    }))
                ->exists();
    }
}
