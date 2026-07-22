<?php

namespace App\Models;

use App\Enums\AtharContributionStatus;
use App\Enums\AtharResponseMode;
use Database\Factories\AtharContributionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AtharContribution extends Model
{
    /** @use HasFactory<AtharContributionFactory> */
    use HasFactory;

    protected $fillable = ['invitation_id', 'status', 'draft_payload', 'sealed_payload', 'source_hash', 'response_mode', 'requested_suggestion', 'draft_updated_at', 'submitted_at', 'deletion_requested_at', 'deleted_at'];

    protected $hidden = ['draft_payload', 'sealed_payload', 'source_hash'];

    protected function casts(): array
    {
        return [
            'status' => AtharContributionStatus::class,
            'response_mode' => AtharResponseMode::class,
            'draft_payload' => 'encrypted:array',
            'sealed_payload' => 'encrypted:array',
            'requested_suggestion' => 'boolean',
            'draft_updated_at' => 'datetime',
            'submitted_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AtharInvitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(AtharInvitation::class);
    }

    /** @return HasMany<AtharPublicationVersion, $this> */
    public function publicationVersions(): HasMany
    {
        return $this->hasMany(AtharPublicationVersion::class, 'contribution_id');
    }

    /** @return HasMany<AtharPublicationConsentEvent, $this> */
    public function consentEvents(): HasMany
    {
        return $this->hasMany(AtharPublicationConsentEvent::class, 'contribution_id');
    }

    public function sealed(): bool
    {
        return $this->sealed_payload !== null && $this->submitted_at !== null;
    }
}
