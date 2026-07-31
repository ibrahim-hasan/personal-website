<?php

namespace App\Models;

use Database\Factories\AtharAccessChallengeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AtharAccessChallenge extends Model
{
    /** @use HasFactory<AtharAccessChallengeFactory> */
    use HasFactory;

    protected $fillable = ['invitation_id', 'code_hash', 'expires_at', 'attempts', 'consumed_at', 'requested_at', 'ip_hash'];

    protected $hidden = ['code_hash', 'ip_hash'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'consumed_at' => 'datetime', 'requested_at' => 'datetime', 'attempts' => 'integer'];
    }

    /** @return BelongsTo<AtharInvitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(AtharInvitation::class);
    }

    public function isUsable(): bool
    {
        return $this->consumed_at === null && ! $this->isExpired() && ! $this->isLocked();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isLocked(): bool
    {
        return $this->attempts >= (int) config('athar.access.code_max_attempts', 6);
    }

    public function attemptsRemaining(): int
    {
        return max(0, (int) config('athar.access.code_max_attempts', 6) - $this->attempts);
    }
}
