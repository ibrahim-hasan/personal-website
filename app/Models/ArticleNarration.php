<?php

namespace App\Models;

use App\Enums\ArticleNarrationStatus;
use Database\Factories\ArticleNarrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ArticleNarration extends Model
{
    /** @use HasFactory<ArticleNarrationFactory> */
    use HasFactory;

    protected $fillable = [
        'article_key',
        'locale',
        'requested_by_user_id',
        'status',
        'source_hash',
        'script',
        'preparation_model',
        'prompt_version',
        'preparation_notes',
        'pronunciation_notes',
        'samples',
        'preparation_started_at',
        'prepared_at',
        'approved_at',
        'failed_at',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'status' => ArticleNarrationStatus::class,
            'preparation_notes' => 'array',
            'pronunciation_notes' => 'array',
            'samples' => 'array',
            'preparation_started_at' => 'datetime',
            'prepared_at' => 'datetime',
            'approved_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by_user_id');
    }

    public function isPreparing(): bool
    {
        return in_array($this->status, [ArticleNarrationStatus::Queued, ArticleNarrationStatus::Preparing], true)
            && ! $this->isPreparationStalled();
    }

    public function isPreparationStalled(): bool
    {
        if (! in_array($this->status, [ArticleNarrationStatus::Queued, ArticleNarrationStatus::Preparing], true)) {
            return false;
        }

        return ($this->preparation_started_at ?? $this->updated_at)?->lt(now()->subMinutes(10)) === true;
    }

    public function isApprovedFor(string $sourceHash): bool
    {
        return $this->approved_at !== null
            && filled($this->script)
            && hash_equals((string) $this->source_hash, $sourceHash);
    }

    public function scriptFingerprint(): string
    {
        return hash('sha256', (string) $this->script);
    }

    /** @return array<string, mixed>|null */
    public function sample(string $modelId): ?array
    {
        $sample = data_get($this->samples, $modelId);

        return is_array($sample) ? $sample : null;
    }

    public function sampleIsGenerating(string $modelId): bool
    {
        return in_array(data_get($this->sample($modelId), 'status'), ['queued', 'processing'], true);
    }

    public function hasCurrentSample(string $modelId): bool
    {
        $sample = $this->sample($modelId);

        return $sample !== null
            && data_get($sample, 'status') === 'ready'
            && hash_equals($this->scriptFingerprint(), (string) data_get($sample, 'script_hash'))
            && filled(data_get($sample, 'path'));
    }

    public function sampleUrl(string $modelId): ?string
    {
        if (! $this->hasCurrentSample($modelId)) {
            return null;
        }

        $sample = $this->sample($modelId);
        $disk = (string) data_get($sample, 'disk', 'public');
        $path = (string) data_get($sample, 'path');

        if ($path === '' || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        return $disk === 'public'
            ? asset('storage/'.ltrim($path, '/'))
            : Storage::disk($disk)->url($path);
    }

    /** @param array<string, mixed> $attributes */
    public function updateSample(string $modelId, array $attributes): void
    {
        $samples = is_array($this->samples) ? $this->samples : [];
        $current = is_array($samples[$modelId] ?? null) ? $samples[$modelId] : [];
        $samples[$modelId] = array_merge($current, $attributes);

        $this->forceFill(['samples' => $samples])->save();
    }
}
