<?php

namespace App\Models;

use App\Enums\ArticleAudioStatus;
use Database\Factories\ArticleAudioFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArticleAudio extends Model
{
    /** @use HasFactory<ArticleAudioFactory> */
    use HasFactory;

    protected $table = 'article_audio';

    protected $fillable = [
        'article_key',
        'locale',
        'requested_by_user_id',
        'status',
        'disk',
        'path',
        'mime_type',
        'file_size',
        'character_count',
        'segment_count',
        'content_hash',
        'voice_id',
        'model_id',
        'output_format',
        'voice_settings',
        'request_ids',
        'queued_at',
        'generation_started_at',
        'generated_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleAudioStatus::class,
            'file_size' => 'integer',
            'character_count' => 'integer',
            'segment_count' => 'integer',
            'voice_settings' => 'array',
            'request_ids' => 'array',
            'queued_at' => 'datetime',
            'generation_started_at' => 'datetime',
            'generated_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isGenerating(): bool
    {
        return in_array($this->status, [ArticleAudioStatus::Queued, ArticleAudioStatus::Processing], true)
            && ! $this->isGenerationStalled();
    }

    public function isGenerationStalled(): bool
    {
        $activityAt = match ($this->status) {
            ArticleAudioStatus::Queued => $this->queued_at ?? $this->updated_at,
            ArticleAudioStatus::Processing => $this->generation_started_at ?? $this->updated_at,
            default => null,
        };

        return $activityAt?->lt(now()->subMinutes(20)) === true;
    }

    public function isReady(): bool
    {
        return $this->status === ArticleAudioStatus::Ready && filled($this->path);
    }

    public function isStale(string $contentHash): bool
    {
        return ! hash_equals((string) $this->content_hash, $contentHash);
    }

    public function publicUrl(): ?string
    {
        if (! $this->isReady() || blank($this->path)) {
            return null;
        }

        if ($this->disk === 'public') {
            return asset('storage/'.ltrim($this->path, '/'));
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function scopeReadyFor(Builder $query, string $articleKey, string $locale, string $contentHash): Builder
    {
        return $query
            ->where('article_key', $articleKey)
            ->where('locale', $locale)
            ->where('status', ArticleAudioStatus::Ready)
            ->where('content_hash', $contentHash)
            ->whereNotNull('path');
    }
}
