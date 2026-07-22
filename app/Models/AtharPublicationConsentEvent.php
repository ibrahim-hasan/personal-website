<?php

namespace App\Models;

use App\Enums\AtharConsentEventType;
use App\Enums\AtharIdentityDisplay;
use App\Enums\AtharPlacement;
use Database\Factories\AtharPublicationConsentEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtharPublicationConsentEvent extends Model
{
    /** @use HasFactory<AtharPublicationConsentEventFactory> */
    use HasFactory;

    protected $fillable = ['contribution_id', 'publication_version_id', 'event_type', 'snapshot_hash', 'approved_locales', 'placement', 'placement_key', 'identity_display', 'privacy_notice_version', 'verification_method', 'ip_hash', 'user_agent_hash', 'occurred_at'];

    protected $hidden = ['snapshot_hash', 'ip_hash', 'user_agent_hash'];

    protected function casts(): array
    {
        return [
            'event_type' => AtharConsentEventType::class,
            'placement' => AtharPlacement::class,
            'identity_display' => AtharIdentityDisplay::class,
            'approved_locales' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<AtharContribution, $this> */
    public function contribution(): BelongsTo
    {
        return $this->belongsTo(AtharContribution::class);
    }

    /** @return BelongsTo<AtharPublicationVersion, $this> */
    public function publicationVersion(): BelongsTo
    {
        return $this->belongsTo(AtharPublicationVersion::class);
    }
}
