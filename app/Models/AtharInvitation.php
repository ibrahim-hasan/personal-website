<?php

namespace App\Models;

use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharInvitationDeliveryMode;
use App\Enums\AtharInvitationStatus;
use App\Enums\AtharPlacement;
use App\Enums\AtharRelationship;
use Database\Factories\AtharInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AtharInvitation extends Model
{
    /** @use HasFactory<AtharInvitationFactory> */
    use HasFactory;

    protected $fillable = [
        'created_by', 'token_hash', 'token_ciphertext', 'email_hash', 'email', 'recipient_name',
        'delivery_mode', 'relationship', 'preferred_locale', 'personal_reason',
        'placement', 'placement_key', 'identity_display', 'status', 'expires_at', 'sent_at',
        'verified_at', 'revoked_at',
    ];

    protected $hidden = ['token_hash', 'token_ciphertext', 'email_hash', 'email', 'recipient_name', 'personal_reason'];

    protected function casts(): array
    {
        return [
            'status' => AtharInvitationStatus::class,
            'delivery_mode' => AtharInvitationDeliveryMode::class,
            'token_ciphertext' => 'encrypted',
            'relationship' => AtharRelationship::class,
            'placement' => AtharPlacement::class,
            'identity_display' => AtharIdentityDisplay::class,
            'expires_at' => 'datetime',
            'sent_at' => 'datetime',
            'verified_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasOne<AtharContribution, $this> */
    public function contribution(): HasOne
    {
        return $this->hasOne(AtharContribution::class, 'invitation_id');
    }

    /** @return HasManyThrough<AtharPublicationVersion, AtharContribution, $this> */
    public function publicationVersions(): HasManyThrough
    {
        return $this->hasManyThrough(AtharPublicationVersion::class, AtharContribution::class, 'invitation_id', 'contribution_id');
    }

    /** @return HasManyThrough<AtharPublicationConsentEvent, AtharContribution, $this> */
    public function consentEvents(): HasManyThrough
    {
        return $this->hasManyThrough(AtharPublicationConsentEvent::class, AtharContribution::class, 'invitation_id', 'contribution_id');
    }

    public function isAccessible(): bool
    {
        return $this->revoked_at === null
            && $this->status !== AtharInvitationStatus::Revoked
            && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
