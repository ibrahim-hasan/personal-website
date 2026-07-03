<?php

namespace App\Models;

use App\Support\DashboardCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GuideDownloader extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'guide_id',
        'email',
        'is_mail_sent',
        'download_token',
        'token_expires_at',
        'token_used_at',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    protected function casts(): array
    {
        return [
            'is_mail_sent' => 'boolean',
            'token_expires_at' => 'datetime',
            'token_used_at' => 'datetime',
        ];
    }

    public function generateDownloadToken(): string
    {
        $token = Str::random(32);

        $this->update([
            'download_token' => $token,
            'token_expires_at' => now()->addHours(24),
            'token_used_at' => null,
        ]);

        return $token;
    }

    public function isTokenValid(?string $token): bool
    {
        if ($token === null) {
            return false;
        }

        if ($this->download_token !== $token) {
            return false;
        }

        if ($this->token_used_at !== null) {
            return false;
        }

        if ($this->token_expires_at === null || $this->token_expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function markTokenAsUsed(string $token): void
    {
        if ($this->isTokenValid($token)) {
            $this->update(['token_used_at' => now()]);
        }
    }

    protected static function booted(): void
    {
        static::saved(fn () => DashboardCache::bust());
        static::deleted(fn () => DashboardCache::bust());
        static::restored(fn () => DashboardCache::bust());
        static::forceDeleted(fn () => DashboardCache::bust());
    }
}
