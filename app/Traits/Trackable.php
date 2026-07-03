<?php

namespace App\Traits;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

trait Trackable
{
    public static function bootTrackable(): void
    {
        static::creating(function ($model): void {
            if (auth()->check()) {
                $model->created_by ??= auth()->id();
                $model->updated_by ??= auth()->id();
            }
        });

        static::updating(function ($model): void {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeOnlyDraft(Builder $query): Builder
    {
        return $query->where('status', 'draft');
    }

    public function scopeWithoutDraft(Builder $query): Builder
    {
        return $query->where('status', '!=', 'draft');
    }

    public function getFormattedDate(string $column = 'created_at', string $format = 'l، j F Y'): string
    {
        return Carbon::parse($this->{$column})->translatedFormat($format);
    }
}
